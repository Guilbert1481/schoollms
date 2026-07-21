<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentRequest;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Document requests: student submits against the (global) documents catalog,
 * the registrar walks the request pending → processing → ready → released.
 */
class DocumentRequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $registrar;

    private User $studentUser;

    private Student $student;

    private Document $document;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->registrar = User::factory()->create(['school_id' => $this->school->id, 'role' => 'registrar']);
        $this->studentUser = User::factory()->create(['school_id' => $this->school->id, 'role' => 'student']);
        $this->student = Student::create(['school_id' => $this->school->id, 'user_id' => $this->studentUser->id, 'student_number' => 'S-'.uniqid(), 'first_name' => 'Ana', 'last_name' => 'Cruz']);

        $this->document = Document::create(['name' => 'Transcript of Records', 'code' => 'TOR-'.uniqid(), 'type' => 'registrar', 'is_active' => true]);
    }

    private function submit(): DocumentRequest
    {
        $this->actingAs($this->studentUser)->post(route('student.services.documents.store'), [
            'document_id' => $this->document->id, 'purpose' => 'Scholarship', 'copies' => 2,
        ]);

        return DocumentRequest::withoutGlobalScopes()->latest('id')->firstOrFail();
    }

    public function test_student_can_request_and_the_catalog_counter_increments(): void
    {
        $this->actingAs($this->studentUser);
        $this->get(route('student.services.documents.index'))->assertOk()->assertSee('Transcript of Records');

        $this->post(route('student.services.documents.store'), [
            'document_id' => $this->document->id, 'purpose' => 'Scholarship', 'copies' => 2,
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('document_requests', [
            'student_id' => $this->student->id, 'document_id' => $this->document->id,
            'status' => 'pending', 'copies' => 2,
        ]);
        $this->assertSame(1, (int) $this->document->fresh()->request_count);
    }

    public function test_duplicate_open_request_for_the_same_document_is_blocked(): void
    {
        $this->submit();

        $this->actingAs($this->studentUser)->post(route('student.services.documents.store'), [
            'document_id' => $this->document->id, 'purpose' => 'Again', 'copies' => 1,
        ])->assertSessionHasErrors('document_id');

        $this->assertSame(1, DocumentRequest::withoutGlobalScopes()->count());
    }

    public function test_registrar_walks_the_request_to_released(): void
    {
        $request = $this->submit();

        $this->actingAs($this->registrar);

        foreach (['processing', 'ready', 'released'] as $step) {
            $this->put(route('registrar.requests.documents.transition', $request), ['action' => $step])
                ->assertSessionHas('success');
            $this->assertDatabaseHas('document_requests', ['id' => $request->id, 'status' => $step]);
        }

        $this->assertNotNull($request->fresh()->released_at);
        $this->assertSame($this->registrar->id, (int) $request->fresh()->handled_by);
    }

    public function test_skipping_lifecycle_steps_is_rejected(): void
    {
        $request = $this->submit();

        $this->actingAs($this->registrar);
        $this->put(route('registrar.requests.documents.transition', $request), ['action' => 'released'])
            ->assertSessionHasErrors('action');

        $this->assertDatabaseHas('document_requests', ['id' => $request->id, 'status' => 'pending']);
    }

    public function test_cross_school_registrar_cannot_transition(): void
    {
        $request = $this->submit();

        $otherSchool = School::factory()->create();
        $otherRegistrar = User::factory()->create(['school_id' => $otherSchool->id, 'role' => 'registrar']);

        $this->actingAs($otherRegistrar);
        $this->put(route('registrar.requests.documents.transition', $request), ['action' => 'processing'])
            ->assertNotFound();

        $this->assertDatabaseHas('document_requests', ['id' => $request->id, 'status' => 'pending']);
    }
}

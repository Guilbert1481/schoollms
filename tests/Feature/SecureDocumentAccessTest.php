<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Roadmap C2 — gated serving of sensitive documents.
 *
 * The owning student and same-school staff can fetch a government ID /
 * enrollment document through the documents.* routes; other students,
 * cross-school staff, and guests cannot, and the files no longer live on
 * the public (web-served) disk.
 */
class SecureDocumentAccessTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;

    private School $schoolB;

    private User $owner;

    private User $peerStudent;

    private User $registrarA;

    private User $registrarB;

    private int $studentId;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        $this->schoolA = School::factory()->create();
        $this->schoolB = School::factory()->create();

        $this->owner = User::factory()->create(['school_id' => $this->schoolA->id, 'role' => 'student']);
        $this->peerStudent = User::factory()->create(['school_id' => $this->schoolA->id, 'role' => 'student']);
        $this->registrarA = User::factory()->create(['school_id' => $this->schoolA->id, 'role' => 'registrar']);
        $this->registrarB = User::factory()->create(['school_id' => $this->schoolB->id, 'role' => 'registrar']);

        Storage::disk('local')->put('id_documents/gov-id.jpg', 'fake-image-bytes');

        $this->studentId = $this->insertWithDefaults('students', [
            'school_id' => $this->schoolA->id,
            'user_id' => $this->owner->id,
            'first_name' => 'Cee',
            'last_name' => 'Two',
            'student_number' => 'C2-001',
            'photo_id' => 'id_documents/gov-id.jpg',
            'status' => 'active',
        ]);
    }

    /** Insert a row, auto-filling NOT NULL columns that have no default. */
    private function insertWithDefaults(string $table, array $values): int
    {
        $cols = DB::select(
            'select column_name, data_type, is_nullable, column_default, extra
             from information_schema.columns
             where table_schema = database() and table_name = ?',
            [$table]
        );
        foreach ($cols as $c) {
            $c = array_change_key_case((array) $c, CASE_LOWER);
            if (array_key_exists($c['column_name'], $values)
                || strtoupper((string) $c['is_nullable']) === 'YES'
                || $c['column_default'] !== null
                || str_contains(strtolower((string) $c['extra']), 'auto_increment')) {
                continue;
            }
            $values[$c['column_name']] = match (true) {
                in_array(strtolower((string) $c['data_type']), ['int', 'bigint', 'smallint', 'tinyint', 'decimal', 'double', 'float']) => 0,
                strtolower((string) $c['data_type']) === 'date' => date('Y-m-d'),
                in_array(strtolower((string) $c['data_type']), ['datetime', 'timestamp']) => date('Y-m-d H:i:s'),
                strtolower((string) $c['data_type']) === 'json' => '[]',
                default => '',
            };
        }

        return (int) DB::table($table)->insertGetId($values);
    }

    private function idUrl(): string
    {
        return route('documents.student-id', $this->studentId);
    }

    public function test_owner_can_download_their_own_government_id(): void
    {
        $this->actingAs($this->owner)->get($this->idUrl())->assertOk();
    }

    public function test_same_school_registrar_can_download(): void
    {
        $this->actingAs($this->registrarA)->get($this->idUrl())->assertOk();
    }

    public function test_same_school_peer_student_gets_404(): void
    {
        $this->actingAs($this->peerStudent)->get($this->idUrl())->assertNotFound();
    }

    public function test_cross_school_registrar_gets_404(): void
    {
        $this->actingAs($this->registrarB)->get($this->idUrl())->assertNotFound();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get($this->idUrl())->assertRedirect();
    }

    public function test_id_upload_no_longer_lands_on_the_public_disk(): void
    {
        Storage::disk('public')->assertMissing('id_documents/gov-id.jpg');
        Storage::disk('local')->assertExists('id_documents/gov-id.jpg');
    }

    public function test_enrollment_document_is_gated_the_same_way(): void
    {
        Storage::disk('local')->put('enrollment-documents/1/tor.pdf', 'fake-pdf');

        $academicYearId = $this->insertWithDefaults('academic_years', [
            'school_id' => $this->schoolA->id,
        ]);
        $termId = $this->insertWithDefaults('terms', [
            'school_id' => $this->schoolA->id,
            'academic_year_id' => $academicYearId,
        ]);
        $enrollmentId = $this->insertWithDefaults('student_enrollments', [
            'school_id' => $this->schoolA->id,
            'student_id' => $this->studentId,
            'academic_year_id' => $academicYearId,
            'term_id' => $termId,
        ]);
        $documentId = $this->insertWithDefaults('enrollment_documents', [
            'school_id' => $this->schoolA->id,
            'enrollment_id' => $enrollmentId,
            'document_type' => 'Transcript of Records',
            'file_path' => 'enrollment-documents/1/tor.pdf',
            'status' => 'pending',
        ]);

        $url = route('documents.enrollment', $documentId);

        $this->actingAs($this->owner)->get($url)->assertOk();
        $this->actingAs($this->registrarA)->get($url)->assertOk();
        $this->actingAs($this->peerStudent)->get($url)->assertNotFound();
        $this->actingAs($this->registrarB)->get($url)->assertNotFound();
    }
}

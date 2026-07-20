<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Roadmap D2 — a minor's government-ID number is encrypted at rest. The value
 * stored in the DB is ciphertext; the model transparently returns plaintext;
 * and the backfill command encrypts pre-existing plaintext rows idempotently.
 */
class StudentIdEncryptionTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = School::factory()->create();
    }

    private function makeStudent(array $attrs = []): Student
    {
        return Student::create(array_merge([
            'school_id' => $this->school->id,
            'student_number' => 'S-'.uniqid(),
            'first_name' => 'Test',
            'last_name' => 'Learner',
        ], $attrs));
    }

    public function test_government_id_is_ciphertext_at_rest_but_plaintext_on_the_model(): void
    {
        $student = $this->makeStudent(['government_id_number' => 'A1-234-567-890']);

        // Raw column value must NOT be the plaintext.
        $raw = DB::table('students')->where('id', $student->id)->value('government_id_number');
        $this->assertNotSame('A1-234-567-890', $raw);
        $this->assertSame('A1-234-567-890', Crypt::decryptString($raw), 'Raw value must be our ciphertext.');

        // The model transparently decrypts on read.
        $this->assertSame('A1-234-567-890', $student->fresh()->government_id_number);
    }

    public function test_backfill_command_encrypts_existing_plaintext_rows(): void
    {
        // Simulate a legacy row written before the cast existed: raw plaintext.
        $id = DB::table('students')->insertGetId([
            'school_id' => $this->school->id,
            'student_number' => 'S-legacy',
            'first_name' => 'Old',
            'last_name' => 'Row',
            'government_id_number' => 'PLAIN-123-456',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Dry run changes nothing.
        $this->artisan('students:encrypt-government-ids')->assertOk();
        $this->assertSame('PLAIN-123-456', DB::table('students')->where('id', $id)->value('government_id_number'));

        // Real run encrypts it.
        $this->artisan('students:encrypt-government-ids --encrypt')->assertOk();

        $raw = DB::table('students')->where('id', $id)->value('government_id_number');
        $this->assertNotSame('PLAIN-123-456', $raw);
        $this->assertSame('PLAIN-123-456', Crypt::decryptString($raw));
        $this->assertSame('PLAIN-123-456', Student::find($id)->government_id_number);
    }

    public function test_backfill_is_idempotent(): void
    {
        $this->makeStudent(['government_id_number' => 'ALREADY-ENC']);

        // A second run must not double-encrypt (would corrupt the value).
        $this->artisan('students:encrypt-government-ids --encrypt')->assertOk();

        $student = Student::firstWhere('student_number', 'like', 'S-%');
        $this->assertSame('ALREADY-ENC', $student->government_id_number);
    }
}

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

    public function test_reading_a_legacy_plaintext_row_does_not_throw_before_backfill(): void
    {
        // A row written before the cast existed: raw plaintext, no ciphertext.
        // The vanilla `encrypted` cast would throw DecryptException on read here
        // (a 500 on every page that shows the ID). EncryptedTolerant must return
        // the plaintext as-is, so the D2 deploy is safe before the backfill runs.
        $id = DB::table('students')->insertGetId([
            'school_id' => $this->school->id,
            'student_number' => 'S-plain',
            'first_name' => 'Legacy',
            'last_name' => 'Plaintext',
            'government_id_number' => 'RAW-PLAIN-999',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame('RAW-PLAIN-999', Student::find($id)->government_id_number);

        // And once the operator does run the backfill, the same row is ciphertext
        // at rest yet still reads back as the same plaintext.
        $this->artisan('students:encrypt-government-ids --encrypt')->assertOk();

        $raw = DB::table('students')->where('id', $id)->value('government_id_number');
        $this->assertNotSame('RAW-PLAIN-999', $raw);
        $this->assertSame('RAW-PLAIN-999', Student::find($id)->government_id_number);
    }

    public function test_a_ciphertext_shaped_value_that_fails_to_decrypt_fails_loud_not_masked(): void
    {
        // A well-formed Laravel envelope whose MAC no longer verifies — the shape
        // an APP_KEY rotation (without APP_PREVIOUS_KEYS), corruption, or tamper
        // produces. Unlike legacy plaintext, the tolerant cast must NOT silently
        // serve this blob as the ID; it must fail loud like the vanilla cast, so
        // a botched rotation is visible instead of printing garbage on an ID card.
        $good = Crypt::encryptString('SECRET-ID-1');
        $payload = json_decode(base64_decode($good), true);
        $payload['mac'] = str_repeat('0', strlen($payload['mac'])); // break the MAC, keep the shape
        $tampered = base64_encode(json_encode($payload));

        $id = DB::table('students')->insertGetId([
            'school_id' => $this->school->id,
            'student_number' => 'S-tamper',
            'first_name' => 'Tam',
            'last_name' => 'Pered',
            'government_id_number' => $tampered,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(\Illuminate\Contracts\Encryption\DecryptException::class);
        Student::find($id)->government_id_number; // must throw, not return the blob
    }

    public function test_blank_id_is_not_stored_as_ciphertext_of_empty(): void
    {
        // set('') must leave '' at rest (mirroring the backfill, which skips
        // blanks), not persist Crypt::encryptString('') — so the cast, backfill,
        // and its isEncrypted() probe agree on what "no value" means.
        $student = $this->makeStudent(['government_id_number' => '']);

        $this->assertSame('', DB::table('students')->where('id', $student->id)->value('government_id_number'));
        $this->assertSame('', $student->fresh()->government_id_number);
    }
}

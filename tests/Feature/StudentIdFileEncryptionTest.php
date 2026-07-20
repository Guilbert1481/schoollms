<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\Services\Uploads\SecureUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Roadmap D2b — uploaded ID files are encrypted at rest. Stored bytes are
 * ciphertext; the gated serve route transparently decrypts; legacy plaintext
 * files still serve; the backfill command is idempotent.
 */
class StudentIdFileEncryptionTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        $this->school = School::factory()->create();
        $this->owner = User::factory()->create(['school_id' => $this->school->id, 'role' => 'student']);
    }

    private function makeStudent(string $photoId): int
    {
        return DB::table('students')->insertGetId([
            'school_id' => $this->school->id,
            'user_id' => $this->owner->id,
            'first_name' => 'Enc',
            'last_name' => 'Ryption',
            'student_number' => 'ENC-'.uniqid(),
            'photo_id' => $photoId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_secure_upload_stores_id_image_as_ciphertext(): void
    {
        $path = app(SecureUpload::class)->storeImageOrDocument(
            UploadedFile::fake()->image('id.jpg', 300, 200),
            'id_documents',
            'local',
            encrypt: true,
        );

        $raw = Storage::disk('local')->get($path);

        // Not a JPEG on disk — it's ciphertext that decrypts to the image bytes.
        $this->assertStringStartsNotWith("\xFF\xD8", $raw, 'Raw bytes must not be a JPEG.');
        $decoded = Crypt::decryptString($raw);
        $this->assertStringStartsWith("\xFF\xD8", $decoded, 'Decrypted bytes must be a JPEG.');
    }

    public function test_serve_route_decrypts_encrypted_id_file(): void
    {
        Storage::disk('local')->put('id_documents/enc.jpg', Crypt::encryptString('SECRET-IMAGE-BYTES'));
        $studentId = $this->makeStudent('id_documents/enc.jpg');

        $res = $this->actingAs($this->owner)->get(route('documents.student-id', $studentId));

        $res->assertOk();
        $this->assertSame('image/jpeg', $res->headers->get('content-type'));
        $this->assertSame('SECRET-IMAGE-BYTES', $res->getContent());
    }

    public function test_serve_route_streams_legacy_plaintext_unchanged(): void
    {
        Storage::disk('local')->put('id_documents/legacy.jpg', 'legacy-plaintext-bytes');
        $studentId = $this->makeStudent('id_documents/legacy.jpg');

        $res = $this->actingAs($this->owner)->get(route('documents.student-id', $studentId));

        $res->assertOk();
        $this->assertSame('legacy-plaintext-bytes', $res->getContent());
    }

    public function test_backfill_encrypts_plaintext_files_idempotently(): void
    {
        Storage::disk('local')->put('id_documents/legacy.jpg', 'plain-bytes');
        $this->makeStudent('id_documents/legacy.jpg');

        // Dry run: unchanged.
        $this->artisan('documents:encrypt-id-files')->assertOk();
        $this->assertSame('plain-bytes', Storage::disk('local')->get('id_documents/legacy.jpg'));

        // Real run: encrypted in place.
        $this->artisan('documents:encrypt-id-files --encrypt')->assertOk();
        $raw = Storage::disk('local')->get('id_documents/legacy.jpg');
        $this->assertNotSame('plain-bytes', $raw);
        $this->assertSame('plain-bytes', Crypt::decryptString($raw));

        // Idempotent: second run must not double-encrypt.
        $this->artisan('documents:encrypt-id-files --encrypt')->assertOk();
        $this->assertSame('plain-bytes', Crypt::decryptString(Storage::disk('local')->get('id_documents/legacy.jpg')));
    }
}

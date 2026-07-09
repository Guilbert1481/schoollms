<?php

namespace Tests\Feature;

use App\Services\Uploads\SecureUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Roadmap H3 — upload hardening. Images are re-encoded (embedded payloads
 * stripped); SVG/HTML and other active-content types are rejected.
 */
class SecureUploadTest extends TestCase
{
    private SecureUpload $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
        $this->service = new SecureUpload;
    }

    public function test_valid_image_is_stored_and_re_encoded(): void
    {
        $file = UploadedFile::fake()->image('avatar.png', 300, 300);

        $path = $this->service->storeImage($file, 'photos', 'public', 'jpg');

        $this->assertNotNull($path);
        $this->assertStringEndsWith('.jpg', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_re_encoding_strips_an_appended_script_payload(): void
    {
        // A real PNG with a script blob appended after the image data — the
        // classic polyglot. Re-encoding must not carry the payload through.
        $raw = (string) UploadedFile::fake()->image('polyglot.png', 64, 64)->get();
        $payload = '<script>alert(document.cookie)</script>';
        $tmp = tempnam(sys_get_temp_dir(), 'poly').'.png';
        file_put_contents($tmp, $raw.$payload);
        $file = new UploadedFile($tmp, 'polyglot.png', 'image/png', null, true);

        $path = $this->service->storeImage($file, 'photos', 'public', 'png');

        $stored = Storage::disk('public')->get($path);
        $this->assertStringNotContainsString('<script>', $stored);
        $this->assertStringNotContainsString('alert(document.cookie)', $stored);
    }

    public function test_svg_is_rejected(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"></svg>';
        $tmp = tempnam(sys_get_temp_dir(), 'evil').'.svg';
        file_put_contents($tmp, $svg);
        $file = new UploadedFile($tmp, 'evil.svg', 'image/svg+xml', null, true);

        $this->expectException(ValidationException::class);
        $this->service->storeImage($file, 'photos', 'public');
    }

    public function test_html_masquerading_as_image_is_rejected(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'evil').'.html';
        file_put_contents($tmp, '<html><script>alert(1)</script></html>');
        $file = new UploadedFile($tmp, 'evil.html', 'text/html', null, true);

        $this->expectException(ValidationException::class);
        $this->service->storeImageOrDocument($file, 'docs', 'local');
    }

    public function test_pdf_document_is_accepted_without_re_encoding(): void
    {
        $file = UploadedFile::fake()->create('transcript.pdf', 200, 'application/pdf');

        $path = $this->service->storeImageOrDocument($file, 'docs', 'local');

        $this->assertNotNull($path);
        $this->assertStringEndsWith('.pdf', $path);
        Storage::disk('local')->assertExists($path);
    }
}

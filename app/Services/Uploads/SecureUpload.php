<?php

namespace App\Services\Uploads;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

/**
 * Centralized defense for user file uploads (Roadmap H3).
 *
 * - Server-side extension/MIME allow-list (never trust the client's
 *   `accept` attribute or reported MIME type alone).
 * - Images are decoded and re-encoded, which strips any embedded scripts
 *   or polyglot payloads (neutralizes SVG/HTML/EXIF stored-XSS). SVG is
 *   deliberately NOT an allowed image type — it is executable markup.
 * - Random stored filenames; the client filename never becomes a path.
 *
 * Throws ValidationException (via the `validate()` helper) on rejection so
 * controllers surface a normal 422, matching the framework's behavior.
 */
class SecureUpload
{
    /** Raster image types we accept and can safely re-encode. */
    public const IMAGE_MIMES = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    /** Documents we accept as-is (no re-encode; validated + random name). */
    public const DOCUMENT_MIMES = ['pdf'];

    /**
     * Store an uploaded image after re-encoding it. Returns the stored path
     * (relative to the disk), or null when no file was provided.
     *
     * @param  string  $format  Output format to normalize to (jpg|png|webp).
     * @param  bool  $encrypt  Encrypt the stored bytes at rest (D2b — for
     *                         crown-jewel files like government IDs). Serving
     *                         code (SecureDocumentController) transparently
     *                         decrypts.
     */
    public function storeImage(
        ?UploadedFile $file,
        string $directory,
        string $disk = 'public',
        string $format = 'jpg',
        ?int $maxWidth = 2000,
        bool $encrypt = false,
    ): ?string {
        if (! $file) {
            return null;
        }

        $this->assertAllowed($file, self::IMAGE_MIMES);

        $manager = ImageManager::gd();
        $image = $manager->read($file->getRealPath());

        if ($maxWidth !== null && $image->width() > $maxWidth) {
            $image->scaleDown(width: $maxWidth);
        }

        $encoded = (string) match ($format) {
            'png' => $image->toPng(),
            'webp' => $image->toWebp(quality: 85),
            default => $image->toJpeg(quality: 85),
        };

        $path = rtrim($directory, '/').'/'.Str::random(40).'.'.$format;
        Storage::disk($disk)->put($path, $encrypt ? Crypt::encryptString($encoded) : $encoded);

        return $path;
    }

    /**
     * Store a document (e.g. PDF) after validating its type. Not re-encoded,
     * so it keeps a random filename and a strict allow-list instead.
     */
    public function storeDocument(
        ?UploadedFile $file,
        string $directory,
        string $disk = 'local',
        array $allowed = self::DOCUMENT_MIMES,
        bool $encrypt = false,
    ): ?string {
        if (! $file) {
            return null;
        }

        $this->assertAllowed($file, $allowed);

        $ext = strtolower($file->getClientOriginalExtension());
        $path = rtrim($directory, '/').'/'.Str::random(40).'.'.$ext;

        if ($encrypt) {
            // Encrypt-at-rest (D2b): read the validated bytes and store
            // ciphertext instead of the raw file.
            Storage::disk($disk)->put($path, Crypt::encryptString((string) file_get_contents($file->getRealPath())));
        } else {
            Storage::disk($disk)->putFileAs(
                rtrim($directory, '/'),
                $file,
                basename($path),
            );
        }

        return $path;
    }

    /**
     * Store either an image (re-encoded) or an allowed document, deciding by
     * the validated extension. Used where a field accepts "PDF or image".
     */
    public function storeImageOrDocument(
        ?UploadedFile $file,
        string $directory,
        string $disk = 'local',
        bool $encrypt = false,
    ): ?string {
        if (! $file) {
            return null;
        }

        $this->assertAllowed($file, [...self::IMAGE_MIMES, ...self::DOCUMENT_MIMES]);

        $ext = strtolower($file->getClientOriginalExtension());

        return in_array($ext, self::DOCUMENT_MIMES, true)
            ? $this->storeDocument($file, $directory, $disk, self::DOCUMENT_MIMES, $encrypt)
            : $this->storeImage($file, $directory, $disk, encrypt: $encrypt);
    }

    /**
     * Reject anything whose real extension or MIME is not in the allow-list.
     * Uses the framework validator so the failure is a normal 422 with a
     * clear message.
     */
    private function assertAllowed(UploadedFile $file, array $allowed): void
    {
        validator(
            ['file' => $file],
            ['file' => ['required', 'file', 'mimes:'.implode(',', $allowed), 'max:5120']],
            ['file.mimes' => 'The upload must be one of: '.implode(', ', $allowed).'.'],
        )->validate();
    }
}

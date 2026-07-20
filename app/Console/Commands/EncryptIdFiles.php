<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Roadmap D2b — one-time (idempotent) backfill that encrypts existing ID
 * files at rest now that uploads and serving are encryption-aware
 * (SecureUpload / SecureDocumentController).
 *
 * Covers the two private-disk locations: students.photo_id (government-ID
 * images/PDFs) and enrollment_documents.file_path (registrar-required docs).
 * Reads each file, skips anything that already decrypts (idempotent), and
 * rewrites the rest as ciphertext in place. Dry-run by default; pass
 * --encrypt to write. Operator-gated — run after a fresh backup.
 */
class EncryptIdFiles extends Command
{
    protected $signature = 'documents:encrypt-id-files {--encrypt : Actually encrypt files (default is a dry run)}';

    protected $description = 'Encrypt existing student ID / enrolment document files at rest (Roadmap D2b)';

    public function handle(): int
    {
        $write = (bool) $this->option('encrypt');

        if (! $write) {
            $this->warn('DRY RUN — no files will be modified. Re-run with --encrypt to apply.');
        }

        $paths = collect()
            ->merge(DB::table('students')->whereNotNull('photo_id')->pluck('photo_id'))
            ->merge(DB::table('enrollment_documents')->whereNotNull('file_path')->pluck('file_path'))
            ->filter()
            ->unique()
            ->values();

        $encrypted = 0;
        $already = 0;
        $missing = 0;

        foreach ($paths as $path) {
            $disk = collect(['local', 'public'])->first(fn ($d) => Storage::disk($d)->exists($path));

            if (! $disk) {
                $missing++;

                continue;
            }

            $raw = Storage::disk($disk)->get($path);

            if ($this->isEncrypted($raw)) {
                $already++;

                continue;
            }

            $encrypted++;

            if ($write) {
                Storage::disk($disk)->put($path, Crypt::encryptString($raw));
            }
        }

        $this->table(
            ['Missing on disk', 'Already encrypted', $write ? 'Encrypted now' : 'Plaintext (would encrypt)'],
            [[$missing, $already, $encrypted]]
        );

        $this->info($write
            ? "Done. Encrypted {$encrypted} file(s)."
            : "Dry run complete. {$encrypted} file(s) would be encrypted.");

        return self::SUCCESS;
    }

    private function isEncrypted(string $raw): bool
    {
        try {
            Crypt::decryptString($raw);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }
}

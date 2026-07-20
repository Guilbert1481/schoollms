<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Roadmap D2 — one-time (idempotent) backfill that encrypts existing plaintext
 * `students.government_id_number` values now that the column carries the
 * `encrypted` cast (App\Models\Student).
 *
 * Reads RAW values (bypassing the cast, which would fail to decrypt plaintext),
 * detects rows that are already ciphertext and skips them, and encrypts the
 * rest with exactly the scheme the cast uses (Crypt::encryptString). Safe to
 * re-run. Dry-run by default — pass --encrypt to write.
 *
 * Operator-gated: run AFTER `php artisan migrate` (which widens the column) and
 * AFTER a fresh backup, because the values become APP_KEY-dependent.
 */
class EncryptGovernmentIds extends Command
{
    protected $signature = 'students:encrypt-government-ids {--encrypt : Actually write encrypted values (default is a dry run)}';

    protected $description = 'Encrypt existing plaintext students.government_id_number values at rest (Roadmap D2)';

    public function handle(): int
    {
        $write = (bool) $this->option('encrypt');
        $plaintext = 0;
        $already = 0;
        $blank = 0;

        if (! $write) {
            $this->warn('DRY RUN — no rows will be modified. Re-run with --encrypt to apply.');
        }

        DB::table('students')
            ->select('id', 'government_id_number')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($write, &$plaintext, &$already, &$blank) {
                foreach ($rows as $row) {
                    $value = $row->government_id_number;

                    if ($value === null || $value === '') {
                        $blank++;

                        continue;
                    }

                    if ($this->isEncrypted($value)) {
                        $already++;

                        continue;
                    }

                    $plaintext++;

                    if ($write) {
                        DB::table('students')
                            ->where('id', $row->id)
                            ->update(['government_id_number' => Crypt::encryptString($value)]);
                    }
                }
            });

        $this->table(
            ['Blank/null', 'Already encrypted', $write ? 'Encrypted now' : 'Plaintext (would encrypt)'],
            [[$blank, $already, $plaintext]]
        );

        $this->info($write
            ? "Done. Encrypted {$plaintext} row(s)."
            : "Dry run complete. {$plaintext} plaintext row(s) would be encrypted.");

        return self::SUCCESS;
    }

    /** True if the value already decrypts under the current APP_KEY. */
    private function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }
}

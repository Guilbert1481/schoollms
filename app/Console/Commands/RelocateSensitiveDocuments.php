<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * One-time (idempotent) migration for Roadmap C2: move sensitive uploads
 * off the web-served public disk onto the private local disk. Relative
 * paths are preserved, so no database values change.
 *
 * Safe to re-run: already-moved files are skipped; a public file whose
 * private twin already exists is left in place and reported (never
 * overwritten blind).
 */
class RelocateSensitiveDocuments extends Command
{
    protected $signature = 'documents:relocate-private {--dry-run : Report what would move without touching files}';

    protected $description = 'Move id_documents/ and enrollment-documents/ from the public disk to the private disk (Roadmap C2)';

    private const PREFIXES = ['id_documents', 'enrollment-documents'];

    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk('local');
        $dry = (bool) $this->option('dry-run');

        $moved = 0;
        $skipped = 0;

        foreach (self::PREFIXES as $prefix) {
            foreach ($public->allFiles($prefix) as $path) {
                if ($private->exists($path)) {
                    $this->warn("CONFLICT (left in place, resolve manually): {$path}");
                    $skipped++;

                    continue;
                }

                if ($dry) {
                    $this->line("would move: {$path}");
                    $moved++;

                    continue;
                }

                $stream = $public->readStream($path);
                if ($stream === null) {
                    $this->error("unreadable, skipped: {$path}");
                    $skipped++;

                    continue;
                }

                $private->writeStream($path, $stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }

                if ($private->exists($path)
                    && $private->size($path) === $public->size($path)) {
                    $public->delete($path);
                    $moved++;
                    $this->line("moved: {$path}");
                } else {
                    $this->error("verify failed, public copy kept: {$path}");
                    $skipped++;
                }
            }
        }

        $verb = $dry ? 'would move' : 'moved';
        $this->info("Done: {$verb} {$moved} file(s), {$skipped} skipped/conflict.");

        return $skipped === 0 ? self::SUCCESS : self::FAILURE;
    }
}

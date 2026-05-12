<?php

namespace App\Console\Commands;

use App\Models\ChatMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PurgeChatAttachments extends Command
{
    protected $signature = 'chat:purge-attachments';
    protected $description = 'Delete chat attachment files older than 24 hours (DB rows kept, paths nulled).';

    public function handle(): int
    {
        $cutoff = now()->subDay();
        $disk = Storage::disk('public');
        $count = 0;

        ChatMessage::whereNotNull('attachment_path')
            ->where('created_at', '<', $cutoff)
            ->chunkById(200, function ($messages) use ($disk, &$count) {
                foreach ($messages as $m) {
                    if ($m->attachment_path && $disk->exists($m->attachment_path)) {
                        $disk->delete($m->attachment_path);
                    }
                    $previewRel = 'chat-attachments/previews/' . $m->id . '.pdf';
                    if ($disk->exists($previewRel)) {
                        $disk->delete($previewRel);
                    }
                    $m->forceFill(['attachment_path' => null])->save();
                    $count++;
                }
            });

        $this->info("Purged {$count} expired chat attachment(s).");
        return self::SUCCESS;
    }
}

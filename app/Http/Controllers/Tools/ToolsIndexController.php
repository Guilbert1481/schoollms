<?php

namespace App\Http\Controllers\Tools;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Process;

class ToolsIndexController extends Controller
{
    public function index()
    {
        $converterEngine = $this->detectConverterEngine();

        return view('tools.index', [
            'pdfConverterAvailable' => $converterEngine !== null,
            'pdfConverterEngine' => $converterEngine,
        ]);
    }

    private function detectConverterEngine(): ?string
    {
        if (class_exists('Imagick')) {
            return 'imagick';
        }

        if ($this->commandExists('pdftoppm')) {
            return 'pdftoppm';
        }

        if ($this->commandExists('magick')) {
            return 'magick';
        }

        return null;
    }

    private function commandExists(string $command): bool
    {
        return $this->resolveCommandPath($command) !== null;
    }

    private function resolveCommandPath(string $command): ?string
    {
        $result = Process::run('where ' . $command);
        if ($result->successful()) {
            $first = trim(strtok($result->output(), "\n"));
            if ($first !== '' && file_exists($first)) {
                return $first;
            }
        }

        $fallbackPatterns = [];

        if ($command === 'magick') {
            $fallbackPatterns = [
                'C:/Program Files/ImageMagick*/magick.exe',
                'C:/Program Files (x86)/ImageMagick*/magick.exe',
            ];
        }

        if ($command === 'pdftoppm') {
            $localAppData = getenv('LOCALAPPDATA') ?: '';

            $fallbackPatterns = [
                ($localAppData !== ''
                    ? str_replace('\\', '/', $localAppData) . '/Microsoft/WinGet/Packages/oschwartz10612.Poppler*/poppler*/Library/bin/pdftoppm.exe'
                    : ''),
                'C:/Program Files/poppler*/Library/bin/pdftoppm.exe',
                'C:/Program Files (x86)/poppler*/Library/bin/pdftoppm.exe',
                'C:/poppler*/Library/bin/pdftoppm.exe',
            ];
        }

        foreach ($fallbackPatterns as $pattern) {
            if ($pattern === '') {
                continue;
            }

            $matches = glob($pattern) ?: [];
            if (!empty($matches)) {
                sort($matches, SORT_NATURAL);
                return end($matches) ?: null;
            }
        }

        return null;
    }
}

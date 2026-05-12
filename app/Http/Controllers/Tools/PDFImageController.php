<?php

namespace App\Http\Controllers\Tools;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class PDFImageController extends Controller
{
    public function convert(Request $request)
    {
        $request->validate([
            'pdf_file' => ['required', 'file', 'mimetypes:application/pdf', 'max:20480'],
            'format' => ['required', 'in:jpg,png'],
            'dpi' => ['required', 'integer', 'min:72', 'max:300'],
        ]);

        $converterEngine = $this->detectConverterEngine();

        if ($converterEngine === null) {
            return back()->withErrors([
                'pdf_file' => 'No PDF converter is available. Install Imagick, Poppler (pdftoppm), or ImageMagick.',
            ])->withInput();
        }

        $pdfFile = $request->file('pdf_file');
        $format = $request->string('format')->toString();
        $dpi = (int) $request->input('dpi');

        $jobId = now()->format('YmdHis') . '_' . Str::random(8);
        $safeBaseName = Str::slug(pathinfo($pdfFile->getClientOriginalName(), PATHINFO_FILENAME));
        $safeBaseName = $safeBaseName !== '' ? $safeBaseName : 'converted';
        $workingDirectory = $this->makeWorkingDirectory((string) auth()->id(), $jobId);
        $sourcePdfPath = $workingDirectory . DIRECTORY_SEPARATOR . 'source_' . $safeBaseName . '.pdf';

        $pdfFile->move($workingDirectory, basename($sourcePdfPath));

        try {
            $imagePaths = $converterEngine === 'imagick'
                ? $this->convertWithImagick($sourcePdfPath, $workingDirectory, $format, $dpi)
                : $this->convertWithCli($sourcePdfPath, $workingDirectory, $format, $dpi, $converterEngine);

            if (empty($imagePaths)) {
                throw new \RuntimeException('No images generated');
            }

            $download = $this->prepareDownloadPayload(
                $workingDirectory,
                $imagePaths,
                $safeBaseName,
                $format
            );

            $this->cleanupIntermediates($workingDirectory, $download['path']);
            $this->clearOutputBuffers();

            return response()
                ->download($download['path'], $download['name'])
                ->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            Log::warning('PDF to image conversion failed', [
                'message' => $e->getMessage(),
                'user_id' => auth()->id(),
                'engine' => $converterEngine,
            ]);

            return back()->withErrors([
                'pdf_file' => 'Conversion failed. Please make sure the PDF is valid and the converter engine is installed correctly.',
            ])->withInput();
        } finally {
            $this->deleteEmptyParentDirectories($workingDirectory);
        }
    }

    private function convertWithImagick(string $absolutePdfPath, string $workingDirectory, string $format, int $dpi): array
    {
        $imagick = new \Imagick();
        $imagick->setResolution($dpi, $dpi);
        $imagick->readImage($absolutePdfPath);

        $imagePaths = [];
        $pageNumber = 1;

        foreach ($imagick as $page) {
            $page->setImageFormat($format);
            $page->setImageCompressionQuality(90);

            if ($format === 'jpg') {
                $page->setImageBackgroundColor('white');
                $page = $page->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
                $page->setImageFormat('jpg');
            }

            $fileName = sprintf('page-%03d.%s', $pageNumber, $format);
            $absoluteOutputPath = $workingDirectory . DIRECTORY_SEPARATOR . $fileName;
            file_put_contents($absoluteOutputPath, $page->getImageBlob());

            $imagePaths[] = [
                'page' => $pageNumber,
                'name' => $fileName,
                'path' => $absoluteOutputPath,
            ];

            $pageNumber++;
            $page->clear();
        }

        $imagick->clear();
        $imagick->destroy();

        return $imagePaths;
    }

    private function convertWithCli(string $absolutePdfPath, string $workingDirectory, string $format, int $dpi, string $engine): array
    {
        $absoluteOutputDirectory = $workingDirectory;

        if (!is_dir($absoluteOutputDirectory)) {
            mkdir($absoluteOutputDirectory, 0755, true);
        }

        if ($engine === 'pdftoppm') {
            $pdftoppmPath = $this->resolveCommandPath('pdftoppm');
            if ($pdftoppmPath === null) {
                throw new \RuntimeException('pdftoppm not found');
            }

            $prefixPath = $absoluteOutputDirectory . DIRECTORY_SEPARATOR . 'page';
            $formatArg = $format === 'png' ? '-png' : '-jpeg';

            $result = Process::run(
                $this->quotePath($pdftoppmPath) . ' -r ' . (int) $dpi . ' ' . $formatArg . ' ' .
                $this->quotePath($absolutePdfPath) . ' ' . $this->quotePath($prefixPath)
            );

            if (!$result->successful()) {
                throw new \RuntimeException('pdftoppm failed');
            }

            $extension = $format === 'png' ? 'png' : 'jpg';
            $generatedFiles = glob($absoluteOutputDirectory . DIRECTORY_SEPARATOR . 'page-*.' . $extension) ?: [];
        } elseif ($engine === 'magick') {
            $magickPath = $this->resolveCommandPath('magick');
            if ($magickPath === null) {
                throw new \RuntimeException('magick not found');
            }

            $outputPattern = $absoluteOutputDirectory . DIRECTORY_SEPARATOR . 'page-%03d.' . $format;
            $flattenArgs = $format === 'jpg' ? ' -background white -alpha remove ' : ' ';

            $result = Process::run(
                $this->quotePath($magickPath) . ' -density ' . (int) $dpi . ' ' .
                $this->quotePath($absolutePdfPath) .
                $flattenArgs .
                $this->quotePath($outputPattern)
            );

            if (!$result->successful()) {
                throw new \RuntimeException('magick failed');
            }

            $generatedFiles = glob($absoluteOutputDirectory . DIRECTORY_SEPARATOR . 'page-*.' . $format) ?: [];
        } else {
            throw new \RuntimeException('Unsupported converter engine');
        }

        sort($generatedFiles, SORT_NATURAL);

        $imagePaths = [];
        foreach ($generatedFiles as $index => $filePath) {
            $fileName = basename($filePath);

            $imagePaths[] = [
                'page' => $index + 1,
                'name' => $fileName,
                'path' => $filePath,
            ];
        }

        return $imagePaths;
    }

    private function makeWorkingDirectory(string $userId, string $jobId): string
    {
        $workingDirectory = storage_path('app/tmp/tools/pdf-to-image/' . $userId . '/' . $jobId);

        if (!is_dir($workingDirectory)) {
            mkdir($workingDirectory, 0755, true);
        }

        return $workingDirectory;
    }

    private function prepareDownloadPayload(string $workingDirectory, array $images, string $safeBaseName, string $format): array
    {
        if (count($images) === 1) {
            $single = $images[0];

            return [
                'path' => $single['path'],
                'name' => $safeBaseName . '_page-1.' . $format,
            ];
        }

        $archivePath = $workingDirectory . DIRECTORY_SEPARATOR . $safeBaseName . '_images.zip';

        try {
            if (file_exists($archivePath)) {
                @unlink($archivePath);
            }

            $archive = new \PharData($archivePath);
            foreach ($images as $image) {
                $archive->addFile($image['path'], $image['name']);
            }

            unset($archive);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Archive packaging failed: ' . $e->getMessage());
        }

        if (!file_exists($archivePath) || filesize($archivePath) === 0) {
            throw new \RuntimeException('Archive packaging failed: archive file was not created');
        }

        return [
            'path' => $archivePath,
            'name' => $safeBaseName . '_images.zip',
        ];
    }

    private function cleanupIntermediates(string $workingDirectory, string $keepPath): void
    {
        if (!is_dir($workingDirectory)) {
            return;
        }

        $items = scandir($workingDirectory);
        if ($items === false) {
            return;
        }

        $normalizedKeep = str_replace('\\', '/', realpath($keepPath) ?: $keepPath);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $workingDirectory . DIRECTORY_SEPARATOR . $item;
            $normalizedCurrent = str_replace('\\', '/', realpath($fullPath) ?: $fullPath);

            if ($normalizedCurrent === $normalizedKeep) {
                continue;
            }

            if (is_dir($fullPath)) {
                $this->deleteDirectory($fullPath);
            } else {
                @unlink($fullPath);
            }
        }
    }

    private function deleteEmptyParentDirectories(string $workingDirectory): void
    {
        if (is_dir($workingDirectory)) {
            $remaining = scandir($workingDirectory);
            if ($remaining !== false && count($remaining) <= 2) {
                @rmdir($workingDirectory);
            }
        }

        $userDir = dirname($workingDirectory);
        if (is_dir($userDir)) {
            $remaining = scandir($userDir);
            if ($remaining !== false && count($remaining) <= 2) {
                @rmdir($userDir);
            }
        }
    }

    private function clearOutputBuffers(): void
    {
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
    }

    private function deleteDirectory(string $path): void
    {
        if (!is_dir($path)) {
            if (is_file($path)) {
                @unlink($path);
            }
            return;
        }

        $items = scandir($path);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $path . DIRECTORY_SEPARATOR . $item;

            if (is_dir($fullPath)) {
                $this->deleteDirectory($fullPath);
            } else {
                @unlink($fullPath);
            }
        }

        @rmdir($path);
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

        if ($command === 'powershell') {
            $fallbackPatterns = [
                'C:/Windows/System32/WindowsPowerShell/v1.0/powershell.exe',
                'C:/Windows/SysWOW64/WindowsPowerShell/v1.0/powershell.exe',
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

    private function quotePath(string $value): string
    {
        return '"' . str_replace('"', '\\"', $value) . '"';
    }
}

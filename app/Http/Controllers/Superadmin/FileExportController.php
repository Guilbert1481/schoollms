<?php

namespace App\Http\Controllers\Superadmin;

namespace App\Http\Controllers\Superadmin;
use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class FileExportController extends Controller
{
    /**
     * Zips and downloads all physical documents for a school.
     */
    public function exportFiles(School $school)
    {
        $zipFileName = "school_files_{$school->slug}.zip";
        $zipPath = storage_path("app/temp/{$zipFileName}");

        // Create the directory if it doesn't exist
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            // Path where this school's files are stored
            // Assuming your structure is: storage/app/public/schools/{id}/...
            $relativeFolder = "public/schools/{$school->id}";
            $filesPath = storage_path("app/{$relativeFolder}");

            if (file_exists($filesPath)) {
                $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($filesPath),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($files as $name => $file) {
                    if (!$file->isDir()) {
                        $filePath = $file->getRealPath();
                        // Create relative path inside the zip
                        $relativePath = substr($filePath, strlen($filesPath) + 1);
                        $zip->addFile($filePath, $relativePath);
                    }
                }
            }
            
            $zip->close();
        }

        // Return the download and delete the temp file after sending
        return response()->download($zipPath)->deleteFileAfterSend(true);
    }
}
<?php

namespace App\Http\Controllers\Superadmin;

namespace App\Http\Controllers\Superadmin;
use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use App\Models\Course;
use App\Models\Invoice;
use Illuminate\Support\Facades\Response;

class ExportController extends Controller
{
    /**
     * Export a complete data archive for a specific school branch.
     */
    public function export(School $school)
    {
        // Filename includes the school slug and a precise timestamp
        $fileName = "school_archive_{$school->slug}_" . now()->format('Ymd_His') . ".json";

        return Response::streamDownload(function () use ($school) {
            $handle = fopen('php://output', 'w');
            
            // Start the JSON structure with school metadata
            fwrite($handle, '{"school_metadata": ' . json_encode([
                'name' => $school->name,
                'export_id' => uniqid('SCH_ARC_'),
                'timestamp' => now()->toIso8601String()
            ]) . ',');

            // 1. Stream Users (Staff, Teachers, Students)
            fwrite($handle, '"users": [');
            User::where('school_id', $school->id)->cursor()->each(function ($user, $index) use ($handle) {
                fwrite($handle, ($index > 0 ? ',' : '') . $user->toJson());
            });
            fwrite($handle, '],');

            // 2. Stream Academic Records
            fwrite($handle, '"academic_data": [');
            Course::where('school_id', $school->id)->cursor()->each(function ($course, $index) use ($handle) {
                fwrite($handle, ($index > 0 ? ',' : '') . $course->toJson());
            });
            fwrite($handle, '],');

            // 3. Stream Financial Records
            fwrite($handle, '"financials": [');
            Invoice::where('school_id', $school->id)->cursor()->each(function ($invoice, $index) use ($handle) {
                fwrite($handle, ($index > 0 ? ',' : '') . $invoice->toJson());
            });
            fwrite($handle, ']}'); // End JSON structure

            fclose($handle);
        }, $fileName, ['Content-Type' => 'application/json']);
    }
}
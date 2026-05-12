<?php

namespace App\Http\Controllers\Training\Trainee;

use App\Http\Controllers\Controller;
use App\Models\TrainingCertificate;


class CertificateController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $profile = $user->profile;
        $trainee = $profile?->trainee;

        if (!$trainee) {
            abort(403, 'No trainee record found.');
        }

        $certificates = TrainingCertificate::with('enrollment.session.course.trainingType')
            ->whereHas('enrollment', function ($query) use ($trainee) {
                $query->where('trainee_id', $trainee->id)
                    ->whereIn('status', ['completed', 'passed', 'completed_passed', 'passed_completed']);
            })
            ->latest()
            ->get();

        $certificates->each(function (TrainingCertificate $certificate) {
            $courseName = (string) ($certificate->course_name ?: data_get($certificate, 'enrollment.session.course.course_name', 'Training Course'));
            $trainingType = (string) ($certificate->training_type_name ?: data_get($certificate, 'enrollment.session.course.trainingType.name', '-'));

            $certificate->setAttribute('training_type_name_label', $trainingType !== '' ? $trainingType : '-');
            $certificate->setAttribute('course_name_label', $courseName !== '' ? $courseName : 'Training Course');
            $certificate->setAttribute('certificate_number_label', $certificate->certificate_number ?: '-');
            $certificate->setAttribute('date_issued_label', $certificate->date_issued
                ? \Carbon\Carbon::parse($certificate->date_issued)->format('F d, Y')
                : '-');
            $certificate->setAttribute('file_url', $certificate->file_path ? asset('storage/' . $certificate->file_path) : null);
            $certificate->setAttribute('preview_url', route('training.trainee.certificates.preview', $certificate->id));
        });

        return view('training.trainee.certificates', compact('certificates'));
    }

    public function preview($id)
    {
        $user = auth()->user();
        $profile = $user->profile;
        $trainee = $profile?->trainee;

        if (!$trainee) {
            abort(403, 'No trainee record found.');
        }

        $certificate = TrainingCertificate::with([
                'enrollment.course',
                'enrollment.trainee.profile',
            ])
            ->whereKey($id)
            ->whereHas('enrollment', function ($query) use ($trainee) {
                $query->where('trainee_id', $trainee->id);
            })
            ->firstOrFail();

        return view('training.certificates.template', compact('certificate'));
    }
}
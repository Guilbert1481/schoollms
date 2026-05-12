<?php

namespace App\Mail;

use App\Models\StudentEnrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EnrollmentSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public StudentEnrollment $enrollment,
        public string $pdfPath,
        public string $recipientName = '',
    ) {}

    public function envelope(): Envelope
    {
        $student = $this->enrollment->student;
        $name    = trim(($student?->first_name ?? '').' '.($student?->last_name ?? ''));
        $school  = $this->enrollment->school?->school_name ?? config('app.name');

        return new Envelope(
            subject: "Enrolment Application Submitted — {$name} ({$school})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.enrollment_submitted',
            with: [
                'enrollment'    => $this->enrollment,
                'student'       => $this->enrollment->student,
                'recipientName' => $this->recipientName,
                'school'        => $this->enrollment->school,
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->pdfPath)
                ->as('enrolment-application.pdf')
                ->withMime('application/pdf'),
        ];
    }
}

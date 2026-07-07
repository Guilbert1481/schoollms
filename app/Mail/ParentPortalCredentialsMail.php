<?php

namespace App\Mail;

use App\Models\FinanceSetting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Parent portal welcome mail: the auto-generated account credentials sent to
 * the guardian email on file when the registrar clears their child's
 * enrollment. The password is one-time — the portal forces a change on
 * first login.
 */
class ParentPortalCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $parent,
        protected string $temporaryPassword,
        public string $studentName,
        public string $schoolName,
        protected ?FinanceSetting $setting = null,
    ) {
    }

    public function envelope(): Envelope
    {
        $from = null;
        if ($this->setting && filled($this->setting->mail_from_address)) {
            $from = new Address(
                $this->setting->mail_from_address,
                $this->setting->mail_from_name ?: $this->schoolName
            );
        }

        return new Envelope(
            from: $from,
            subject: 'Your Parent Portal account — '.$this->schoolName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.parent_portal_credentials',
            with: [
                'parentName'        => trim(($this->parent->first_name ?? '').' '.($this->parent->last_name ?? '')),
                'email'             => $this->parent->email,
                'temporaryPassword' => $this->temporaryPassword,
                'studentName'       => $this->studentName,
                'schoolName'        => $this->schoolName,
                'loginUrl'          => route('login'),
            ],
        );
    }
}

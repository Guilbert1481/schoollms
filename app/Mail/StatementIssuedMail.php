<?php

namespace App\Mail;

use App\Models\FinanceSetting;
use App\Models\StatementOfAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Statement of Account PDF emailed with every 3rd auto-sent invoice, so the
 * family gets a running summary of the account alongside the bill.
 */
class StatementIssuedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public StatementOfAccount $statement,
        protected string $pdfData,
        public string $recipientName = '',
        protected ?FinanceSetting $setting = null,
    ) {
    }

    public function envelope(): Envelope
    {
        $schoolName = $this->statement->school->school_name ?? 'School';

        $from = null;
        if ($this->setting && filled($this->setting->mail_from_address)) {
            $from = new Address($this->setting->mail_from_address, $this->setting->mail_from_name ?: $schoolName);
        }

        return new Envelope(
            from: $from,
            subject: 'Statement of Account '.($this->statement->soa_number ?: $this->statement->id).' — '.$schoolName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.statement_issued',
            with: [
                'statement'     => $this->statement,
                'recipientName' => $this->recipientName,
                'schoolName'    => $this->statement->school->school_name ?? 'School',
            ],
        );
    }

    public function attachments(): array
    {
        $pdf = $this->pdfData;

        return [
            Attachment::fromData(fn () => $pdf, 'SOA-'.($this->statement->soa_number ?: $this->statement->id).'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}

<?php

namespace App\Mail;

use App\Models\FinanceSetting;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Invoice PDF emailed to the student and their guardians when the invoice's
 * billing date arrives (finance auto-send). PDF bytes are passed in so the
 * document is rendered once per invoice, not once per recipient.
 */
class InvoiceIssuedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        protected string $pdfData,
        public string $recipientName = '',
        protected ?FinanceSetting $setting = null,
    ) {
    }

    public function envelope(): Envelope
    {
        $schoolName = $this->invoice->school->school_name ?? 'School';

        $from = null;
        if ($this->setting && filled($this->setting->mail_from_address)) {
            $from = new Address($this->setting->mail_from_address, $this->setting->mail_from_name ?: $schoolName);
        }

        return new Envelope(
            from: $from,
            subject: 'Invoice '.($this->invoice->invoice_number ?: $this->invoice->id).' — '.$schoolName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice_issued',
            with: [
                'invoice'       => $this->invoice,
                'recipientName' => $this->recipientName,
                'schoolName'    => $this->invoice->school->school_name ?? 'School',
            ],
        );
    }

    public function attachments(): array
    {
        $pdf = $this->pdfData;

        return [
            Attachment::fromData(fn () => $pdf, 'Invoice-'.($this->invoice->invoice_number ?: $this->invoice->id).'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}

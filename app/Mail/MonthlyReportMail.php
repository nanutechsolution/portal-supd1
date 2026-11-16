<?php

namespace App\Mail;

use App\Support\MonthlyReport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MonthlyReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public MonthlyReport $report,
        public string $attachmentPath,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Laporan Bulanan '.$this->report->periodLabel().' — Portal SUPD I',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.monthly-report',
            with: [
                'report' => $this->report,
                'summary' => $this->report->summary(),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->attachmentPath)
                ->as($this->report->filename())
                ->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ];
    }
}

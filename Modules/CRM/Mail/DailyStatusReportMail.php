<?php

namespace Modules\CRM\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

use function Symfony\Component\Clock\now;

class DailyStatusReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var string
     */
    public $pdfContent;

    /**
     * @var string
     */
    public $filename;

    /**
     * Create a new message instance.
     */
    public function __construct(string $pdfContent, string $filename)
    {
        $this->pdfContent = $pdfContent;
        $this->filename = $filename;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this
            ->subject("CRM Status Report - " . now()->format('d M Y'))
            ->view('crm::mail.daily_status_report')
            ->attachData(
                $this->pdfContent,
                $this->filename,
                [
                    'mime' => 'application/pdf',
                ]
            );
    }
}

<?php

namespace Modules\HRM\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AttendanceReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @var string */
    public $customSubject;
    /** @var array */
    public $viewData;
    /** @var string */
    public $pdfContent;
    /** @var string */
    public $filename;

    /**
     * Create a new message instance.
     */
    public function __construct(string $subject, array $viewData, string $pdfContent, string $filename = 'attendance_report.pdf')
    {
        $this->customSubject = $subject;
        $this->viewData = $viewData;
        $this->pdfContent = $pdfContent;
        $this->filename = $filename;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this
            ->subject($this->customSubject)
            ->view('hrm::mail.attendance-report', $this->viewData)
            ->attachData($this->pdfContent, $this->filename, [
                'mime' => 'application/pdf',
            ]);
    }
}

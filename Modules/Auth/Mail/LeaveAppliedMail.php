<?php

namespace Modules\Auth\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Modules\HRM\Models\Employee;
use Modules\HRM\Models\Leave;

class LeaveAppliedMail extends Mailable
{
    public Employee $manager;
    public Leave $leave;

    /**
     * Create a new message instance.
     */
    public function __construct(Employee $manager, Leave $leave)
    {
        $this->manager = $manager;
        $this->leave = $leave;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
                subject: 'New Leave Application Submitted'
        );
    }

    /**
     * Get the message content.
     */
    public function content(): Content
    {
        return new Content(
            view: 'hrm::emails.leave-applied',
            with: [
                'manager' => $this->manager,
                'leave'   => $this->leave,
                ],
        );
    }
}

<?php

namespace Modules\Auth\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use App\Models\User;
use Modules\HRM\Models\Leave;

class LeaveStatusUpdatedMail extends Mailable
{
    public Leave $leave;
    public ?User $actor;
    public string $statusType; // 'manager' or 'hr'
    public string $action; // 'approved' or 'rejected'

    /**
     * Create a new message instance.
     */
    public function __construct(Leave $leave, ?User $actor, string $statusType, string $action)
    {
        $this->leave = $leave;
        $this->actor = $actor;
        $this->statusType = $statusType;
        $this->action = $action;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Leave Status Updated'
        );
    }

    /**
     * Get the message content.
     */
    public function content(): Content
    {
        return new Content(
            view: 'hrm::emails.leave-status-updated',
            with: [
                'leave' => $this->leave,
                'actor' => $this->actor,
                'statusType' => $this->statusType,
                'action' => $this->action,
            ]
        );
    }
}


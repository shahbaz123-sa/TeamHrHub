<?php

namespace Modules\CRM\Notifications\Rfq;

use Modules\CRM\Models\Rfq;
use Illuminate\Bus\Queueable;
use Modules\CRM\Services\NodeApiService;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\CRM\Notifications\Channels\WebhookChannel;

class StatusUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $tries = 5;

    public $timeout = 120;

    public $maxExceptions = 1;

    protected $rfq;

    public function __construct(Rfq $rfq)
    {
        $this->rfq = $rfq;
        $this->onConnection('crm');
    }

    public function via($notifiable)
    {
        return WebhookChannel::class;
    }

    public function toWebhook($notifiable)
    {
        app(NodeApiService::class)->notifyB2bRfqStatusUpdate($this->rfq, $this->rfq->status);
        return true;
    }
}

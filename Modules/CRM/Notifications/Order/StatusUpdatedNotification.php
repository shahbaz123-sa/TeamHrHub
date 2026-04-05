<?php

namespace Modules\CRM\Notifications\Order;

use Illuminate\Bus\Queueable;
use Modules\CRM\Models\Order;
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

    protected $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
        $this->onConnection('crm');
    }

    public function via($notifiable)
    {
        return WebhookChannel::class;
    }

    public function toWebhook($notifiable)
    {
        app(NodeApiService::class)->notifyOrderStatusUpdate($this->order, $this->order->status);
        return true;
    }
}

<?php

namespace Modules\CRM\Notifications\Channels;

use Illuminate\Notifications\Notification;

use function Laravel\Prompts\info;

class WebhookChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        $message = $notification->toWebhook($notifiable);

        info('Webhook notification sent', ['message' => $message]);
    }
}

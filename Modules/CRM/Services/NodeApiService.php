<?php

namespace Modules\CRM\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Modules\CRM\Models\Order;
use Modules\CRM\Models\Rfq;

class NodeApiService
{
    /**
     * @var string
     */
    private $nodeApiUrl;

    /**
     * @var \Illuminate\Support\Facades\Http
     */
    private $nodeApiClient;

    public function __construct()
    {
        $this->nodeApiClient = Http::timeout(30);
        $this->nodeApiUrl = config('services.node.api_url');

        throw_if(!$this->nodeApiUrl, new Exception('NODE_API_URL not configured for order status notification'));
    }

    public function notifyOrderStatusUpdate(Order $order, $status)
    {
        throw_if(empty($status), new Exception("Status is invalid", 422));

        if ($order->order_type === 'B2B') {
            return $this->notifyB2bOrderStatusUpdate($order, $status);
        } elseif ($order->order_type === 'B2C') {
            return $this->notifyB2cOrderStatusUpdate($order, $status);
        }

        throw new Exception("Invalid order type", 422);
    }

    public function notifyB2cOrderStatusUpdate(Order $order, $status)
    {
        $payload = http_build_query([
            'order_id' => $order->id,
            'status' => $status,
            'user_id' => $order->user_id,
        ]);

        $response = $this->nodeApiClient
            ->post("{$this->nodeApiUrl}/order/order-status-update?$payload");

        throw_if($response->failed(), new Exception("API Error: " . data_get($response->json(), 'message', 'Something went wrong')));

        return $response;
    }

    public function notifyB2bOrderStatusUpdate(Order $order, $status)
    {
        $payload = [
            'entity_id' => $order->id,
            'company_id' => $order->company_id,
            'status' => $status,
            'user_id' => $order->user_id,
            'type' => 'order',
        ];

        return $this->notifyB2bStatusUpdate($payload);
    }

    public function notifyB2bRfqStatusUpdate(Rfq $rfq, $status)
    {
        $payload = [
            'entity_id' => $rfq->id,
            'company_id' => $rfq->company_id,
            'status' => $status,
            'user_id' => $rfq->user_id,
            'type' => 'rfq',
        ];

        return $this->notifyB2bStatusUpdate($payload);
    }

    private function notifyB2bStatusUpdate($payload)
    {
        $response = $this->nodeApiClient
            ->post("{$this->nodeApiUrl}/b2b/b2b-status-update", $payload);

        throw_if($response->failed(), new Exception("API Error: " . data_get($response->json(), 'message', 'Something went wrong')));

        return $response;
    }
}

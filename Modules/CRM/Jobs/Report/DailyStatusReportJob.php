<?php

namespace Modules\CRM\Jobs\Report;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Modules\CRM\Http\Resources\CreditApplicationResource;
use Modules\CRM\Http\Resources\OrderResource;
use Modules\CRM\Http\Resources\RfqResource;
use Modules\CRM\Http\Resources\SupplierResource;
use Modules\CRM\Mail\DailyStatusReportMail;
use Modules\CRM\Models\CreditApplication;
use Modules\CRM\Models\EmailSetting;
use Modules\CRM\Models\Order;
use Modules\CRM\Models\Rfq;
use Modules\CRM\Models\Supplier;

class DailyStatusReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $todayDate = now()->format('Y-m-d');

        $orders = Order::with(['customer', 'items.product'])
            ->whereDate('updatedAt', $todayDate)
            ->get();

        $rfqs = Rfq::with(['user', 'company', 'item'])
            ->whereDate('updated_at', $todayDate)
            ->get();

        $creditReqs = CreditApplication::with(['user', 'company'])
            ->whereDate('updatedAt', $todayDate)
            ->get();

        $suppliers = Supplier::whereDate('updated_at', $todayDate)->get();

        $statusColors = [
            'pending' => 'text-bg-warning',
            'awaiting_payment' => 'text-bg-info',
            'processing' => 'text-bg-info',
            'quotation_sent' => 'text-bg-info',
            'quotation_received' => 'text-bg-info',
            'approved' => 'text-bg-success',
            'completed' => 'text-bg-success',
            'rejected' => 'text-bg-danger',
            'cancelled' => 'text-bg-danger',
            'refunded' => 'text-bg-warning',
        ];

        $pdf = Pdf::setPaper('a3', 'landscape')->loadView('crm::report.daily_status_report', [
            'orders' => OrderResource::collection($orders)->response(request())->getData(true)["data"],
            'rfqs' => RfqResource::collection($rfqs)->response(request())->getData(true)['data'],
            'creditRequests' => CreditApplicationResource::collection($creditReqs)->response(request())->getData(true)['data'],
            'suppliers' => SupplierResource::collection($suppliers)->response(request())->getData(true)['data'],
            'statusColors' => $statusColors
        ]);

        $filename = "daily_report_$todayDate.pdf";
        $pdfContent = $pdf->output();

        $recipients = EmailSetting::where('slug', 'daily-status-report')
            ->where('is_active', true)
            ->latest('id')
            ->value('recipients');

        if ($recipients) {
            Mail::to(explode(',', $recipients))
                ->send(new DailyStatusReportMail(
                    $pdfContent,
                    $filename
                ));
        }
    }
}

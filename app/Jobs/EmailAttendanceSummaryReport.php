<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Modules\HRM\Mail\AttendanceReportMail;
use Modules\HRM\Models\AttendanceReportRecipient;
use Modules\HRM\Http\Controllers\AttendanceController;

class EmailAttendanceSummaryReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $period = 'daily',   // daily|weekly|monthly|annual
    ) {}

    public function backoff(): array
    {
        return [10, 20, 30];
    }

    public function handle():void
    {
        try {
            $period = $this->period;
            $recipients = AttendanceReportRecipient::query()
                ->where('is_active', true)
                ->with('employee:id,official_email,personal_email')
                ->get();

            if(!$recipients || $recipients->isEmpty()){
                Log::info('No recipients for attendance summary report', ['period' => $period]);
                return;
            }
            $to = $recipients
                ->map(function ($row) {
                    return $row->employee?->official_email
                        ?? $row->employee?->personal_email
                        ?? null;
                })->filter()->unique()->values()->all();

            if (empty($to)) {
                Log::warning('No recipient emails configured for attendance summary report');
                return;
            }

            $tz = 'Asia/Karachi';
            [$title, $reportRange] = $this->makeRangeAndTitle($tz);

            // Use the AttendanceController to generate the same payload as the PDF export path
            /** @var AttendanceController $controller */
            $controller = app(AttendanceController::class);
            $reportRequest = new Request(['month' => $reportRange]);

            $response = $controller->getAttendanceSummaryReport($reportRequest);
            $data = json_decode($response->getContent(), false);

            $rawSummary = is_array($data) ? ($data['data'] ?? []) : ($data->data ?? []);
            $summary = collect($rawSummary)->map(function($row){
                return is_array($row) ? (object) $row : $row;
            })->all();

            $filtersOut = is_array($data) ? ($data['filters'] ?? []) : (array)($data->filters ?? []);

            // Add start_date and end_date to filters for the view (format like controller)
            $today = Carbon::today('Asia/Karachi');
            $endDate = Carbon::parse($reportRange)->endOfMonth();
            $filtersOut['start_date'] = Carbon::parse($reportRange)->startOfMonth()->isoFormat('dddd, DD-MMM-YYYY');
            $filtersOut['end_date'] = $endDate->greaterThan($today)
                ? $today->isoFormat('dddd, DD-MMM-YYYY')
                : $endDate->isoFormat('dddd, DD-MMM-YYYY');

            $viewData = [
                'summary' => $summary,
                'filters' => $filtersOut,
                'generated_at' => now()->format('d-m-Y H:i:s'),
            ];

            $filename = 'attendance_summary_report_' . $reportRange . '.pdf';

            // render pdf using same blade as controller
            $pdf = Pdf::loadView('hrm::reports.attendance-summary-pdf', $viewData);
            $pdf->setPaper('A4', 'landscape');

            $pdfContent = $pdf->output();

            Mail::to($to)->send(new AttendanceReportMail(
                subject: $title,
                viewData: [
                    'title'        => $title,
                    'generated_at' => now($tz),
                    'range'         => $reportRange,
                ],
                pdfContent: $pdfContent,
                filename: $filename
            ));

            Log::info('EmailAttendanceSummaryReport finished (mail sent)', ['to' => $to, 'period' => $period]);
        } catch (\Throwable $e) {
            Log::error('EmailAttendanceSummaryReport job failed', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    private function makeRangeAndTitle(string $tz): array
    {
        $today = Carbon::today($tz);
        return match ($this->period) {
            'last-month' => [
                'Monthly Attendance Summary Report',
                $today->subMonthNoOverflow()->isoFormat('MMMM-YYYY'),
            ],
            'monthly' => [
                'Monthly Attendance Summary Report',
                $today->isoFormat('MMMM-YYYY'),
            ],
        };
    }
}


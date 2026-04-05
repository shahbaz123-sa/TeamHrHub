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
use Barryvdh\DomPDF\Facade\Pdf;
use Modules\HRM\Contracts\AttendanceRepositoryInterface;
use Modules\HRM\Mail\AttendanceReportMail;
use Modules\HRM\Models\AttendanceReportRecipient;

class EmailAttendanceMonthlyReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $period = 'monthly',   // default monthly; supports daily|weekly|monthly|annual|yesterday
    ) {}

    public function backoff(): array
    {
        return [10, 20, 30];
    }

    public function handle(AttendanceRepositoryInterface $attendanceRepository): void
    {
        try {
            $period = $this->period;
            $duration = match ($period) {
                'last-month' => 'monthly',
                'today', 'yesterday'       => 'daily',
                default                    => $this->period,
            };

            // Collect active recipients subscribed for this period
            $recipients = AttendanceReportRecipient::query()
                ->where('is_active', true)
                ->where($duration, true)
                ->get();
            $to = $recipients->map(function ($row) {
                return $row->employee?->official_email
                    ?? $row->employee?->personal_email
                    ?? null;
            })->filter()->unique()->values()->all();

            if (empty($to)) {
                Log::warning('[EmailAttendanceMonthlyReport] No recipients configured for period', ['period' => $period]);
                return;
            }

            $tz = 'Asia/Karachi';
            [$start, $end, $title, $reportRange] = $this->makeRangeAndTitle($period, $tz);
//dd($start, $end, $title, $reportRange);
            // Filters for repository export; per_page -1 to export all
            $filters = [
                'start_date' => $start,
                'end_date'   => $end,
                'month'      => Carbon::parse($start)->month, // for monthly summary
                'per_page'   => -1,
                'sortBy'     => 'date,employee.department.name,employee',
                'orderBy'    => 'asc,asc,asc',
            ];

            // Get attendance data using existing repository method and render Blade to PDF
            $attendanceData = $attendanceRepository->getMonthlyEmployeeSummary($filters);

            $viewData = [
                'month' => $attendanceData['filters']['month'],
                'rows' => $attendanceData['rows'],
                'filters' => $filters,
            ];

            $pdf = Pdf::loadView('hrm::reports.attendance-monthly-pdf', $viewData);
            $filename = 'attendance_monthly_' . $start . '_' . $end . '.pdf';
            $pdfContent = $pdf->output();

            // Send consolidated email to unique recipients
            Mail::to($to)->send(new AttendanceReportMail(
                subject: $title,
                viewData: [
                    'title'        => $title,
                    'generated_at' => now($tz),
                    'range'        => $reportRange,
                ],
                pdfContent: $pdfContent,
                filename: $filename
            ));

            Log::info('[EmailAttendanceMonthlyReport] Mail sent', [
                'period' => $period,
                'start'  => $start,
                'end'    => $end,
                'to'     => $to,
                'filename' => $filename,
            ]);
        } catch (\Throwable $e) {
            Log::error('[EmailAttendanceMonthlyReport] job failed', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
            throw $e;
        }
    }

    /**
     * Make date range and email title for given period.
     * Returns [start_date, end_date, title, human_range]
     */
    private function makeRangeAndTitle($period, string $tz): array
    {
        $today = Carbon::today($tz);

        return match ($period) {
            'yesterday' => [
                $today->copy()->subDay()->toDateString(),
                $today->copy()->subDay()->toDateString(),
                "Yesterday's Attendance Report",
                $today->copy()->subDay()->isoFormat('dddd, DD-MMM-YYYY'),
            ],
            'daily' => [
                $today->toDateString(),
                $today->toDateString(),
                'Daily Attendance Report',
                $today->isoFormat('dddd, DD-MMM-YYYY'),
            ],
            'weekly' => [
                $today->copy()->subDay()->startOfWeek(Carbon::MONDAY)->toDateString(),
                $today->copy()->subDay()->endOfWeek(Carbon::SUNDAY)->toDateString(),
                'Weekly Attendance Report',
                $today->copy()->subDay()->startOfWeek(Carbon::MONDAY)->isoFormat('dddd, DD-MMM-YYYY')
                . ' to ' .
                $today->copy()->subDay()->endOfWeek(Carbon::SUNDAY)->isoFormat('dddd, DD-MMM-YYYY'),
            ],
            'monthly' => [
                $today->copy()->subDay()->startOfMonth()->toDateString(),
                $today->copy()->subDay()->endOfMonth()->toDateString(),
                'Monthly Attendance Report',
                $today->copy()->subDay()->isoFormat('MMMM YYYY'),
            ],
            'last-month' => [
                $today->copy()->subMonth()->startOfMonth()->toDateString(),
                $today->copy()->subMonth()->endOfMonth()->toDateString(),
                'Monthly Attendance Report',
                $today->copy()->subMonth()->isoFormat('MMMM YYYY'),
            ],
            'annual' => [
                $today->copy()->startOfYear()->toDateString(),
                $today->copy()->endOfYear()->toDateString(),
                'Annual Attendance Report',
                $today->isoFormat('YYYY'),
            ],
            default => [
                $today->toDateString(),
                $today->toDateString(),
                'Attendance Report',
                $today->isoFormat('dddd, DD-MMM-YYYY'),
            ],
        };
    }
}


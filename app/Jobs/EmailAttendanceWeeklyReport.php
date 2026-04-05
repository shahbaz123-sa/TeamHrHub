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

class EmailAttendanceWeeklyReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $period = 'weekly',   // default weekly; supports daily|weekly|monthly|annual|yesterday
    ) {}

    public function backoff(): array
    {
        return [10, 20, 30];
    }

    public function handle(AttendanceRepositoryInterface $attendanceRepository): void
    {
        try {
            $period = $this->period;

//            dd($period);
            // Collect active recipients subscribed for this period
            $recipients = AttendanceReportRecipient::query()
                ->where('is_active', true)
                ->where($period, true)
                ->get();
            $to = $recipients->map(function ($row) {
                return $row->employee?->official_email
                    ?? $row->employee?->personal_email
                    ?? null;
            })->filter()->unique()->values()->all();

            if (empty($to)) {
                Log::warning('[EmailAttendanceWeeklyReport] No recipients configured for period', ['period' => $period]);
                return;
            }

            $tz = 'Asia/Karachi';
            [$start, $end, $title, $reportRange] = $this->makeRangeAndTitle($tz);
//dd($start, $end, $title, $reportRange);
            // Filters for repository export; per_page -1 to export all
            $filters = [
                'start_date' => $start,
                'end_date'   => $end,
                'per_page'   => -1,
                'sortBy'     => 'date,employee.department.name,employee',
                'orderBy'    => 'asc,asc,asc',
            ];

            // Get attendance data using existing repository method and render Blade to PDF
            $attendanceData = $attendanceRepository->getAttendanceWeeklyReport($filters, null);
//dd($attendanceData);
            $viewData = [
                'dates' => $attendanceData['dates'],
                'employees' => $attendanceData['employees'],
                'summary' => $attendanceData['summary'],
                'department_summary' => $attendanceData['department_summary'],
                'department_grand' => $attendanceData['department_grand'],
                'filters' => $filters,
            ];
//dd($viewData);
            $pdf = Pdf::loadView('hrm::reports.attendance-weekly-pdf', $viewData);
            $filename = 'attendance_weekly_' . $start . '_' . $end . '.pdf';
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

            Log::info('[EmailAttendanceWeeklyReport] Mail sent', [
                'period' => $period,
                'start'  => $start,
                'end'    => $end,
                'to'     => $to,
                'filename' => $filename,
            ]);
        } catch (\Throwable $e) {
            Log::error('[EmailAttendanceWeeklyReport] job failed', [
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
    private function makeRangeAndTitle(string $tz): array
    {
        $today = Carbon::today($tz);

        return match ($this->period) {
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
                $today->copy()->startOfMonth()->toDateString(),
                $today->copy()->endOfMonth()->toDateString(),
                'Monthly Attendance Report',
                $today->isoFormat('MMMM YYYY'),
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


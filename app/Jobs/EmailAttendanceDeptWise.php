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
use Modules\HRM\Contracts\AttendanceRepositoryInterface;
use Modules\HRM\Mail\AttendanceReportMail;
use Modules\HRM\Models\AttendanceReportRecipient;

class EmailAttendanceDeptWise implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $period = 'daily',   // daily|weekly|monthly|annual
    ) {}

    public function backoff(): array
    {
        return [10, 20, 30];
    }

    public function handle(AttendanceRepositoryInterface $attendanceRepository):void
    {
        try {
            $period = $this->period;
            $recipients = AttendanceReportRecipient::query()
                ->where('is_active', true)
                ->where($period, true) // yesterday | daily | weekly | monthly | annual
                ->with('employee:id,official_email,personal_email')
                ->get();
            if(!$recipients){
                return;
            }
            $to = $recipients
                ->map(function ($row) {
                    return $row->employee?->official_email
                        ?? $row->employee?->email
                        ?? null;
                })->filter()->unique()->values()->all();

            if (empty($to)) {
                Log::warning('CEO_EMAIL not configured');
                return;
            }

            $tz = 'Asia/Karachi';
            [$start, $end, $title, $reportRange] = $this->makeRangeAndTitle($tz);
            $filters = [
                'start_date' => $start,
                'end_date'   => $end,
                'per_page'   => -1,
                'sortBy'     => 'date,employee.department.name,employee',
                'orderBy'    => 'asc,asc,asc',
            ];

            $pdf = $attendanceRepository->exportPdfDepartmentBelow($filters, true);
            $pdfContent = $pdf['file']->output();

            Mail::to($to)->send(new AttendanceReportMail(
                subject: $title,
                viewData: [
                    'title'        => $title,
                    'generated_at' => now('Asia/Karachi'),
                    'range'         => $reportRange,
                ],
                pdfContent: $pdfContent,
                filename: $pdf['filename']
            ));
            Log::info('EmailAttendanceDeptBelow finished (mail sent)', ['to' => $to]);
        } catch (\Throwable $e) {
            Log::error('EmailAttendanceDeptBelow job failed', [
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
            'yesterday' => [
                $today->subDay()->toDateString(),
                $today->toDateString(),
                'Yesterday\'s Attendance Report',
                $today->isoFormat('dddd, DD-MMM-YYYY'),
            ],
            'daily' => [
                $today->toDateString(),
                $today->toDateString(),
                'Daily Attendance Report',
                $today->isoFormat('dddd, DD-MMM-YYYY'),
            ],

            // Rolling 7 days: last 6 days + today
            'weekly' => [
                $today->copy()->subDays(7)->toDateString(),
                $today->copy()->subDay()->toDateString(),
                'Weekly Attendance Report',
                $today->copy()->subDays(7)->isoFormat('dddd, DD-MMM-YYYY')
                . ' to ' .
                $today->subDay()->isoFormat('dddd, DD-MMM-YYYY'),
            ],

            // Month: start of (given month) -> end of (given month) OR current month to today
            'monthly' => [
                $today->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                $today->copy()->subMonthNoOverflow()->endOfMonth()->toDateString(),
                'Monthly Attendance Report',
                $today->subDay()->isoFormat('MMMM-YYYY'),
            ],

            // Annual: Jan 1 -> Dec 31 for given year OR current year to today
            'annual' => [
                $today->copy()->subDay()->startOfYear()->toDateString(),
                $today->copy()->subDay()->toDateString(),
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


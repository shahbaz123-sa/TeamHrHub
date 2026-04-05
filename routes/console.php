<?php

use App\Jobs\AddAttendanceRecords;
use App\Jobs\EmailAttendanceMonthlyReport;
use App\Jobs\EmailAttendanceWeeklyReport;
use App\Jobs\UpdateAttendanceStatus;
use App\Jobs\EmailAttendanceDeptWise;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new AddAttendanceRecords)->dailyAt('00:10')->timezone('Asia/Karachi');
Schedule::job(new UpdateAttendanceStatus)->cron('15,45 * * * *')->timezone('Asia/Karachi');

// Send attendance report: yesterday|daily|weekly|monthly|annual
Schedule::job(new EmailAttendanceDeptWise('yesterday'))->dailyAt('11:00')->timezone('Asia/Karachi');

Schedule::job(new EmailAttendanceDeptWise('daily'))->dailyAt('12:00')->timezone('Asia/Karachi');

Schedule::job(new EmailAttendanceWeeklyReport('weekly'))->weeklyOn(1, '08:00')->timezone('Asia/Karachi');

Schedule::job(new EmailAttendanceMonthlyReport('last-month'))->monthlyOn(01, '00:01')->timezone('Asia/Karachi');

Schedule::job(new EmailAttendanceMonthlyReport('monthly'))->monthlyOn(17, '11:00')->timezone('Asia/Karachi');

Schedule::job(new EmailAttendanceDeptWise('last-month'))->monthlyOn(1, '09:00')->timezone('Asia/Karachi');

// CRM Scheduled Jobs
Schedule::job(new \Modules\CRM\Jobs\Report\DailyStatusReportJob())->dailyAt('23:45')->timezone('Asia/Karachi');

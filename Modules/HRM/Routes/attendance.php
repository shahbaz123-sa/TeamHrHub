<?php

use Illuminate\Support\Facades\Route;
use Modules\HRM\Http\Controllers\AttendanceController;

Route::get('attendance/my-attendance', [AttendanceController::class, 'myAttendance']);
Route::get('attendance', [AttendanceController::class, 'index']);
Route::post('attendance/import', [AttendanceController::class, 'import']);
Route::get('attendanceExport/pdf', [AttendanceController::class, 'exportPdf']);
Route::get('attendanceExport/pdf-dept-below', [AttendanceController::class, 'exportPdfDepartmentBelow']);
Route::get('attendanceExport/excel', [AttendanceController::class, 'exportExcel']);
Route::post('attendanceExport/email-pdf', [AttendanceController::class, 'emailPdf']);
Route::get('attendance/stats', [AttendanceController::class, 'stats']);
Route::put('attendance/{id}', [AttendanceController::class, 'update']);

// Employee Attendance Report
Route::get('reports/employee-daily-attendance', [AttendanceController::class, 'employeeDailyAttendance'])->middleware('can:attendance_summary_report.read');
Route::get('reports/employee-daily-attendance/export-pdf', [AttendanceController::class, 'exportEmployeeDailyAttendancePdf'])->middleware('can:attendance_summary_report.read');
Route::get('reports/employee-daily-attendance/export-excel', [AttendanceController::class, 'exportEmployeeDailyAttendanceExcel'])->middleware('can:attendance_summary_report.read');

//Attendance Summary
Route::get('reports/attendance-summary', [AttendanceController::class, 'getAttendanceSummaryReport'])->middleware('can:employee_attendance_status_report.read');
Route::get('reports/attendance-summary/export-pdf', [AttendanceController::class, 'exportAttendanceSummaryPdf'])->middleware('can:employee_attendance_status_report.read');
Route::get('reports/attendance-summary/export-excel', [AttendanceController::class, 'exportAttendanceSummaryExcel'])->middleware('can:employee_attendance_status_report.read');

// Monthly attendance report
Route::get('reports/attendance-monthly', [AttendanceController::class, 'getAttendanceMonthlyReport'])->middleware('can:monthly_attendance_report.read');
Route::get('reports/attendance-monthly/export-pdf', [AttendanceController::class, 'exportAttendanceMonthlyPdf'])->middleware('can:monthly_attendance_report.read');
Route::get('reports/attendance-monthly/export-excel', [AttendanceController::class, 'exportAttendanceMonthlyExcel'])->middleware('can:monthly_attendance_report.read');

// Weekly attendance report (handled by AttendanceController)
Route::get('reports/attendance-weekly', [AttendanceController::class, 'getAttendanceWeeklyReport'])->middleware('can:weekly_attendance_report.read');
Route::get('reports/attendance-weekly/export-pdf', [AttendanceController::class, 'exportAttendanceWeeklyPdf'])->middleware('can:weekly_attendance_report.read');
Route::get('reports/attendance-weekly/export-excel', [AttendanceController::class, 'exportAttendanceWeeklyExcel'])->middleware('can:weekly_attendance_report.read');

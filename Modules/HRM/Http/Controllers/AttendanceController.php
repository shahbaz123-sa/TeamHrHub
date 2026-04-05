<?php

namespace Modules\HRM\Http\Controllers;

use App\Jobs\EmailAttendanceDeptWise;
use App\Jobs\EmailAttendanceMonthlyReport;
use App\Jobs\EmailAttendanceSummaryReport;
use App\Jobs\EmailAttendanceWeeklyReport;
use App\Jobs\UpdateAttendanceStatus;
use App\Jobs\AddAttendanceRecords;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Modules\HRM\Contracts\AttendanceRepositoryInterface;
use Modules\HRM\Exports\AttendanceMonthlyExport;
use Modules\HRM\Exports\AttendancesExport;
use Modules\HRM\Exports\AttendanceSummaryExport;
use Modules\HRM\Http\Requests\AttendanceFilterRequest;
use Modules\HRM\Http\Requests\AttendanceImportRequest;
use Modules\HRM\Http\Requests\AttendanceUpdateRequest;
use Modules\HRM\Http\Requests\CheckInRequest;
use Modules\HRM\Http\Requests\CheckOutRequest;
use Modules\HRM\Http\Resources\AttendanceCollection;
use Modules\HRM\Http\Resources\AttendanceResource;
use Modules\HRM\Mail\AttendanceReportMail;
use Modules\HRM\Models\Attendance;
use Modules\HRM\Models\Department;

class AttendanceController extends Controller
{
    protected $attendanceRepository;

    public function __construct(AttendanceRepositoryInterface $attendanceRepository)
    {
        $this->attendanceRepository = $attendanceRepository;
    }

    public function run(Request $request)
    {
        $query = trim($request->input('query'));

        if (empty($query)) {
            return response()->json(['success' => false, 'error' => 'Query is required'], 400);
        }

        // Only allow SELECT queries for safety
        if (!preg_match('/^\s*select/i', $query)) {
            return response()->json(['success' => false, 'error' => 'Only SELECT queries are allowed'], 403);
        }

        try {
            // DB::select accepts a string directly
            $result = DB::select($query);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateStatusJob($period)
    {
        try{
            $job = new AddAttendanceRecords();
//            $job = new UpdateAttendanceStatus();
            $result = $job->handle();

//            $result = EmailAttendanceDeptWise::dispatchSync($period);
//            $result = EmailAttendanceWeeklyReport::dispatchSync($period);
//            $result = EmailAttendanceMonthlyReport::dispatchSync($period);
//            $result = EmailAttendanceSummaryReport::dispatchSync('last-month');

            return response()->json([
                'success' => true,
                'result' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate PDF: ' . $e->getMessage()
            ], 500);
        }
    }
    public function checkIn(CheckInRequest $request)
    {
        try {
            $user = Auth::user();
            $validated = $request->validated();
            $address = $this->_getAddressFromCoordinates($validated['latitude'], $validated['longitude']);
            $validated['check_in_address'] = $address;

            $now = Carbon::now('Asia/Karachi');
            $isMarked = Attendance::where('employee_id', $user->employee->id)
                ->where('date', $now->format('Y-m-d'))
                ->whereNotNull('check_in')->exists();

            if($isMarked){
                return response()->json([
                    'message' => 'You already marked attendance',
                    'data' => []
                ], 404);
            }
            
            $attendance = $this->attendanceRepository->checkIn(
                $user->employee->id,
                $validated
            );

            return response()->json([
                'message' => 'Checked in successfully',
                'data' => new AttendanceResource($attendance)
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function checkOut(CheckOutRequest $request)
    {
        $employeeId = Auth::user()->employee->id;
        $validated = $request->validated();
        $address = $this->_getAddressFromCoordinates($validated['latitude'], $validated['longitude']);
        $validated['check_out_address'] = $address;

        $now = Carbon::now('Asia/Karachi');
        $isMarked = Attendance::where('employee_id', $employeeId)
            ->where('date', $now->format('Y-m-d'))
            ->where(function ($q) {
                $q->whereNotNull('check_out')
                    ->orWhereNull('check_in');
            })->exists();

        if($isMarked){
            return response()->json([
                'message' => 'You cannot checkout at this time',
                'data' => []
            ], 404);
        }
        $attendance = $this->attendanceRepository->checkOut($employeeId, $validated);

        return response()->json([
            'message' => 'Checked out successfully',
            'data' => new AttendanceResource($attendance)
        ], Response::HTTP_OK);
    }

    private function _getAddressFromCoordinates($latitude, $longitude)
    {
        $client = new Client();
        $response = $client->get('https://maps.googleapis.com/maps/api/geocode/json', [
            'query' => [
                'latlng' => $latitude . ',' . $longitude,
                'key' => config('services.google_maps.api_key'),
            ],
        ]);

        $data = json_decode($response->getBody(), true);
        if ($data['status'] === 'OK' && count($data['results']) > 0) {
            $best = $this->pickBestGeocodeResult($data['results'] ?? []);
            return $best['formatted_address'] ?? null;
        }
        return null;
    }

    function pickBestGeocodeResult(array $results): ?array
    {
        // Priority from most specific to least
        $priority = [
            'street_address',
            'premise',
            'subpremise',
            'establishment',
            'point_of_interest',
            'route',
            'neighborhood',
            'sublocality_level_1',
            'sublocality',
            'locality',
            'administrative_area_level_3',
            'administrative_area_level_2',
            'administrative_area_level_1',
            'country',
        ];

        $best = null;
        $bestScore = PHP_INT_MAX;

        foreach ($results as $r) {
            $types = $r['types'] ?? [];

            // skip pure plus code result
            if (in_array('plus_code', $types, true) && count($types) === 1) {
                continue;
            }

            // Find best (lowest) priority index among this result's types
            $score = PHP_INT_MAX;
            foreach ($types as $t) {
                $idx = array_search($t, $priority, true);
                if ($idx !== false) {
                    $score = min($score, $idx);
                }
            }

            if ($score < $bestScore) {
                $bestScore = $score;
                $best = $r;
            }
        }

        return $best ?? ($results[0] ?? null);
    }

    public function myAttendance(AttendanceFilterRequest $request)
    {
        $employeeId = Auth::user()->employee->id ?? null;
        
        if ($employeeId) {
            return new AttendanceCollection($this->attendanceRepository->getEmployeeAttendance($employeeId, $request->validated()));
        }

        return ['data' => []];
    }

    public function index(AttendanceFilterRequest $request)
    {
        $attendances = $this->attendanceRepository->getAttendanceByDateRange($request->validated());
        
        return response()->json([
            'data' => AttendanceResource::collection($attendances->items()),
            'meta' => [
                'current_page' => $attendances->currentPage(),
                'last_page' => $attendances->lastPage(),
                'per_page' => $attendances->perPage(),
                'total' => $attendances->total(),
                'from' => $attendances->firstItem(),
                'to' => $attendances->lastItem(),
            ],
            'links' => [
                'first' => $attendances->url(1),
                'last' => $attendances->url($attendances->lastPage()),
                'prev' => $attendances->previousPageUrl(),
                'next' => $attendances->nextPageUrl(),
            ]
        ]);
    }

    public function import(AttendanceImportRequest $request)
    {
        $imported = $this->attendanceRepository->importAttendances(
            $this->processImportFile($request->file('file'))
        );

        if ($imported) {
            return response()->json([
                'message' => 'Attendances imported successfully'
            ], Response::HTTP_CREATED);
        }

        return response()->json([
            'message' => 'Failed to import attendances'
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function exportOld(AttendanceFilterRequest $request)
    {
        $data = $this->attendanceRepository->exportAttendances($request->validated());
        $fileName = 'attendances_export_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new AttendancesExport($data), $fileName);
    }

    public function exportPdf(AttendanceFilterRequest $request)
    {
        try {
            ini_set('memory_limit', '1024M');
            ini_set('max_execution_time', 300);
            $filters = $request->validated();
            $filters['per_page'] = -1; // Get all records

            $filters['sortBy'] = 'date,employee.department.name,employee';
            $filters['orderBy'] = 'asc,asc,asc';

            $attendances = $this->attendanceRepository->getAttendanceByDateRange($filters);
            $attendanceData = AttendanceResource::collection($attendances->items())->toArray($request);

            usort($attendanceData, function ($a, $b) {
                $dateA = $a['date'] ?? '';
                $dateB = $b['date'] ?? '';
                if ($dateA !== $dateB) {
                    return strcmp($dateA, $dateB);
                }

                $deptA = mb_strtolower($a['department'] ?? '');
                $deptB = mb_strtolower($b['department'] ?? '');
                if ($deptA !== $deptB) {
                    return strcmp($deptA, $deptB);
                }

                $nameA = mb_strtolower($a['employee_name'] ?? '');
                $nameB = mb_strtolower($b['employee_name'] ?? '');
                return strcmp($nameA, $nameB);
            });

            $data = [
                'attendances' => $attendanceData,
                'filters' => $filters,
                'generated_at' => now()->format('d-m-Y H:i:s'),
                'total_records' => $attendances->total(),
            ];
            $filename = 'attendance_report';

            if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                $filename .= '_' . $filters['start_date'] . '_to_' . $filters['end_date'];
            } elseif (!empty($filters['start_date'])) {
                $filename .= '_from_' . $filters['start_date'];
            } elseif (!empty($filters['end_date'])) {
                $filename .= '_until_' . $filters['end_date'];
            }
            if (!empty($filters['status'])) {
                $filename .= '_' . $filters['status'];
            }

            // Generate PDF
            $pdf = Pdf::loadView('hrm::attendance.pdf-export', $data);
            $pdf->setPaper('A3', 'portrait'); // Landscape for better table display
            return $pdf->download($filename . '.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportExcel(AttendanceFilterRequest $request)
    {
        try {
            // Get attendance data with all records (no pagination for export)
            $filters = $request->validated();
            $filters['per_page'] = -1; // Get all records

            $attendanceData = $this->attendanceRepository->getAttendanceForReport($filters);
            // Prepare data for PDF
            $data = [
                'attendances' => $attendanceData,
                'filters' => $filters,
                'generated_at' => now()->format('d-m-Y H:i:s'),
                'total_records' => 20,
            ];

            // Generate filename
            $filename = 'attendance_report';

            if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                $filename .= '_' . $filters['start_date'] . '_to_' . $filters['end_date'];
            } elseif (!empty($filters['start_date'])) {
                $filename .= '_from_' . $filters['start_date'];
            } elseif (!empty($filters['end_date'])) {
                $filename .= '_until_' . $filters['end_date'];
            }

            if (!empty($filters['status'])) {
                $filename .= '_' . $filters['status'];
            }

            return Excel::download(
                new AttendancesExport($data),
                $filename . '.xlsx'
            );
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate Excel: ' . $e->getMessage()
            ], 500);
        }
    }

    public function stats()
    {
        $stats = $this->attendanceRepository->getAttendanceStats();
        return response()->json($stats);
    }

    public function update(AttendanceUpdateRequest $request, int $id)
    {
        try {
            $updatedAttendance = $this->attendanceRepository->updateAttendance($id, $request->validated());

            if (!$updatedAttendance) {
                return response()->json([
                    'message' => 'Attendance record not found'
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'message' => 'Attendance updated successfully',
                'data' => new AttendanceResource($updatedAttendance)
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating attendance: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function exportPdfDepartmentBelow(AttendanceFilterRequest $request)
    {
        try {
            ini_set('memory_limit', '1024M');
            ini_set('max_execution_time', 300);
            $pdf = $this->attendanceRepository->exportPdfDepartmentBelow($request);
            return $pdf['file']->download($pdf['filename']);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate department-below PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    protected function processImportFile($file): array
    {
        // Implementation for processing import file
        return [];
    }

    /**
     * Get attendance summary report with month-wise statistics
     */
    public function getAttendanceSummaryReport(Request $request)
    {
        try {
            $tz = $tz ?? 'Asia/Karachi';
            $month = $request->input('month', now()->format('Y-m'));

            // Parse month (expected format YYYY-MM)
            try {
                $dt = Carbon::parse($month . '-01', $tz)->startOfMonth();
            } catch (\Exception $e) {
                $dt = Carbon::now();
            }
            $year = $dt->year;
            $monthNumber = $dt->month;

            $user = auth()->user();
            $empId = $user->employee?->id;

            // Fetch attendance rows via Eloquent (models), restrict to active employees and role-based access
            $rows = Attendance::with('employee')
                ->whereHas('employee', function ($q) {
                    $q->whereNull('deleted_at')
                        ->where('employment_status_id', 1)
                        ->where(function ($q) {
                            $q->whereDoesntHave('exemption')
                                ->orWhereHas('exemption', fn($ex) => $ex->where('attendance_exemption', false));
                        });
                })
                ->when($user->onlyEmployee() && $empId, fn($q) =>
                    $q->where('employee_id', $empId)
                )
                ->when($user->hasRole('Manager') && !$user->hasRole(['Hr']), function ($q) use ($empId) {
                    $q->where(function ($w) use ($empId) {
                        $w->where('employee_id', $empId)
                          ->orWhereHas('employee', fn($e) => $e->where('reporting_to', $empId));
                    });
                })
                ->whereYear('date', $year)
                ->whereMonth('date', $monthNumber)
                ->select(['id', 'employee_id', 'date', 'status', 'late_minutes'])
                ->orderBy('date')
                ->get();

            // Group by date and aggregate in PHP (no query builder aggregations)
            $grouped = $rows->groupBy(function ($row) {
                return Carbon::parse($row->date)->format('Y-m-d');
            });

            $summary = [];
            foreach ($grouped as $date => $items) {
                $totalEmployees = $items->pluck('employee_id')->unique()->count();

                $present = 0; $absent = 0; $late = 0;
                $approvedLeaves = 0; $fullLeaves = 0; $halfLeaves = 0; $shortLeaves = 0;
                $presentOnTime = 0; $presentOrLate = 0;

                foreach ($items as $row) {
                    $status = strtolower(trim($row->status ?? ''));

                    if ($status === 'present') {
                        $present++;
                        $presentOrLate++;
                        if ((int)($row->late_minutes ?? 0) === 0) {
                            $presentOnTime++;
                        }
                    } elseif ($status === 'late') {
                        $late++;
                        $presentOrLate++;
                    } elseif ($status === 'absent') {
                        $absent++;
                    }

                    // Leave derivation from status text
                    if (str_contains($status, 'leave')) {
                        $approvedLeaves++;
                    }
                    if (str_contains($status, 'full')) {
                        $fullLeaves++;
                    }
                    if (str_contains($status, 'half')) {
                        $halfLeaves++;
                    }
                    if (str_contains($status, 'short')) {
                        $shortLeaves++;
                    }
                }

                $onTimePct = $presentOrLate > 0
                    ? round(($presentOnTime / $presentOrLate) * 100, 1)
                    : null;

                $summary[] = (object) [
                    'date' => $date,
                    'total_employees' => $totalEmployees,
                    'present_count' => $present,
                    'present_or_late_count' => $presentOrLate,
                    'absent_count' => $absent,
                    'approved_leaves' => $approvedLeaves,
                    'full_leaves' => $fullLeaves,
                    'half_leaves' => $halfLeaves,
                    'short_leaves' => $shortLeaves,
                    'late_count' => $late,
                    'on_time_percentage' => $onTimePct,
                ];
            }

            // Ensure date ascending order
            usort($summary, function ($a, $b) {
                return strcmp($a->date, $b->date);
            });

            return response()->json([
                'success' => true,
                'data' => $summary,
                'filters' => [
                    'month' => $month,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch attendance summary: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportAttendanceSummaryPdf(Request $request)
    {
        try {
            $month = $request->input('month', now()->format('Y-m'));

            $reportRequest = new Request([
                'month' => $month,
            ]);

            $response = $this->getAttendanceSummaryReport($reportRequest);
            $data = json_decode($response->getContent(), false);

            $rawSummary = is_array($data) ? ($data['data'] ?? []) : ($data->data ?? []);
            $summary = collect($rawSummary)->map(function($row){
                return is_array($row) ? (object) $row : $row;
            })->all();
            $filtersOut = is_array($data) ? ($data['filters'] ?? []) : (array)($data->filters ?? []);

            // Add start_date and end_date to filters for the view
            $filtersOut['start_date'] = Carbon::parse($month)->startOfMonth()->isoFormat('dddd, DD-MMM-YYYY');
            $filtersOut['end_date'] = Carbon::parse($month)->endOfMonth()->isoFormat('dddd, DD-MMM-YYYY');

            $viewData = [
                'summary' => $summary,
                'filters' => $filtersOut,
                'generated_at' => now()->format('d-m-Y H:i:s'),
            ];

            $filename = 'attendance_summary_report_' . $month;

            $pdf = Pdf::loadView('hrm::reports.attendance-summary-pdf', $viewData);
            $pdf->setPaper('A4', 'portrait');

            return $pdf->download($filename . '.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportAttendanceSummaryExcel(Request $request)
    {
        try {
            $month = $request->input('month', now()->format('Y-m'));

            $reportRequest = new Request([
                'month' => $month,
            ]);

            $response = $this->getAttendanceSummaryReport($reportRequest);
            $data = json_decode($response->getContent(), false);

            $rawSummary = is_array($data) ? ($data['data'] ?? []) : ($data->data ?? []);
            $summary = collect($rawSummary)->map(function($row){
                return is_array($row) ? (object) $row : $row;
            })->all();
            $filtersOut = is_array($data) ? ($data['filters'] ?? []) : (array)($data->filters ?? []);

            // Add start_date and end_date to filters for the export class
            $filtersOut['start_date'] = Carbon::parse($month)->startOfMonth()->format('Y-m-d');
            $filtersOut['end_date'] = Carbon::parse($month)->endOfMonth()->format('Y-m-d');

            $filename = 'attendance_summary_report_' . $month . '.xlsx';

            return Excel::download(
                new AttendanceSummaryExport($summary, $filtersOut),
                $filename
            );

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate Excel: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Employee Daily Attendance Report API
     */
    public function employeeDailyAttendance(Request $request)
    {
        $month = $request->input('month');
        $departmentId = $request->input('department_id');
        $search = $request->input('q');
        $data = $this->attendanceRepository->getEmployeeDailyAttendanceForMonth($month, $departmentId, $search);
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function exportEmployeeDailyAttendancePdf(Request $request)
    {
        $month = $request->input('month');
        $departmentId = $request->input('department_id');
        $department = Department::where('id', $departmentId)->pluck('name')->first();
        $data = $this->attendanceRepository->getEmployeeDailyAttendanceForMonth($month, $departmentId,);
        $pdf = Pdf::loadView('hrm::attendance.employee-daily-pdf', ['data' => $data, 'month' => $month, 'department' => $department]);
        return $pdf->download('employee_daily_attendance_' . $month . '.pdf');
    }

    public function exportEmployeeDailyAttendanceExcel(Request $request)
    {
        $month = $request->input('month');
        $departmentId = $request->input('department_id');
        $data = $this->attendanceRepository->getEmployeeDailyAttendanceForMonth($month, $departmentId);
        return Excel::download(new \Modules\HRM\Exports\EmployeeDailyAttendanceExport($data, $month), 'employee_daily_attendance_' . $month . '.xlsx');
    }

    /**
     * Weekly attendance summary report (date range)
     */
    public function getAttendanceWeeklyReport(Request $request)
    {
        try {
            $filters = $request->all();
            $user = auth()->user();
            $result = $this->attendanceRepository->getAttendanceWeeklyReport($filters, $user);

            // Normalize repository result into a single data object that frontend expects
            $dataPayload = [
                'dates' => $result['dates'] ?? [],
                'employees' => $result['employees'] ?? [],
                'summary' => $result['summary'] ?? [],
                'department_summary' => $result['department_summary'] ?? [],
                'department_grand' => $result['department_grand'] ?? [],
                'filters' => $result['filters'] ?? [],
            ];

            return response()->json([
                'success' => true,
                'data' => $dataPayload,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch weekly attendance: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportAttendanceWeeklyPdf(Request $request)
    {
        try {
            ini_set('memory_limit', '1024M');
            ini_set('max_execution_time', 300);
            $filters = $request->all();
            $user = auth()->user();

            $result = $this->attendanceRepository->getAttendanceWeeklyReport($filters, $user);

            $viewData = [
                'summary' => $result['summary'] ?? [],
                'department_summary' => $result['department_summary'] ?? [],
                'department_grand' => $result['department_grand'] ?? [],
                'filters' => $result['filters'] ?? [],
                'dates' => $result['dates'] ?? [],
                'employees' => $result['employees'] ?? [],
                'generated_at' => now()->format('d-m-Y H:i:s'),
            ];

            $label = ($viewData['filters']['start_date'] ?? ($start ?? now()->toDateString()));
            $filename = 'attendance_weekly_' . $label;

            $viewName = 'hrm::reports.attendance-weekly-pdf';

            $pdf = Pdf::loadView($viewName, $viewData);
            $pdf->setPaper('A3', 'landscape');

            return $pdf->download($filename . '.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate weekly PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportAttendanceWeeklyExcel(Request $request)
    {
        try {
            $filters = $request->all();
            $user = auth()->user();

            $result = $this->attendanceRepository->getAttendanceWeeklyReport($filters, $user);
            $summary = $result['summary'] ?? [];

            // Map summary items into objects accepted by AttendanceSummaryExport
            $excelData = array_map(function ($item) {
                if (is_array($item)) $item = (object) $item;
                return $item;
            }, $summary);

            $filtersOut = $result['filters'] ?? [];
            $label = ($filtersOut['start_date'] ?? ($start ?? now()->toDateString()));
            $filename = 'attendance_weekly_' . $label . '.xlsx';

            return Excel::download(
                new AttendanceSummaryExport($excelData, $filtersOut),
                $filename
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate weekly Excel: ' . $e->getMessage()
            ], 500);
        }
    }

    public function emailPdf(AttendanceFilterRequest $request)
    {
        try {
            $to = env('CEO_EMAIL');
            if (empty($to)) {
                return response()->json([
                    'success' => false,
                    'message' => 'CEO_EMAIL not configured. Set it in .env or pass ?to=email@example.com',
                ], 422);
            }

            // Today
            $today = Carbon::today();
            $request['start_date'] = $request['end_date'] = $today->toDateString();
            $title = 'Daily Attendance Report';

            $pdf = $this->attendanceRepository->exportPdfDepartmentBelow($request);
            $pdfContent = $pdf['file']->output();

            // Send email
            Mail::to($to)->send(new AttendanceReportMail(
                subject: $title,
                viewData: [
                    'title' => $title,
                    'generated_at' => now(),
                    'date' => $request['end_date'],
                ],
                pdfContent: $pdfContent,
                filename: $pdf['filename']
            ));

            return response()->json([
                'success' => true,
                'message' => 'Email sent to ' . $to,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getAttendanceMonthlyReport(Request $request)
    {
        try {
            $month = $request->get('month'); // format YYYY-MM
            $departmentId = $request->get('department_id');
            $search = $request->get('q');

            if (!$month) {
                // fallback: if start_date provided, derive month
                $start = $request->get('start_date');
                if ($start) $month = substr($start, 0, 7);
            }

            $data = $this->attendanceRepository->getMonthlyEmployeeSummary([
                'month' => $month,
                'department_id' => $departmentId,
                'q' => $search,
            ]);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch monthly summary: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function exportAttendanceMonthlyPdf(Request $request)
    {
        try {
            $month = $request->get('month');
            if (!$month) {
                $start = $request->get('start_date');
                if ($start) $month = substr($start, 0, 7);
            }
            $departmentId = $request->get('department_id');
            $search = $request->get('q');

            $result = $this->attendanceRepository->getMonthlyEmployeeSummary([
                'month' => $month,
                'department_id' => $departmentId,
                'q' => $search,
            ]);

            // Normalize rows to arrays for Blade
            $rows = array_map(function ($r) {
                if (is_object($r)) $r = (array)$r;
                // Make sure keys exist and email is available
                $r['name'] = $r['name'] ?? ($r['employee_name'] ?? '-');
                $r['employee_code'] = $r['employee_code'] ?? ($r['code'] ?? '');
                $r['email'] = $r['email'] ?? ($r['official_email'] ?? ($r['employee_email'] ?? ''));
                return $r;
            }, $result['rows'] ?? []);

            $viewData = [
                'rows' => $rows,
                'dates' => $result['dates'] ?? [],
                'month' => $month,
                'generated_at' => now()->format('d-m-Y H:i:s'),
            ];
            $filename = 'attendance_monthly_' . ($month ?? now()->format('Y-m')) . '.pdf';

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('hrm::reports.attendance-monthly-pdf', $viewData);
            $pdf->setPaper('A4', 'landscape');
            return $pdf->download($filename);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate monthly PDF: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function exportAttendanceMonthlyExcel(Request $request)
    {
        try {
            $month = $request->get('month');
            if (!$month) {
                $start = $request->get('start_date');
                if ($start) $month = substr($start, 0, 7);
            }
            $departmentId = $request->get('department_id');
            $search = $request->get('q');

            $result = $this->attendanceRepository->getMonthlyEmployeeSummary([
                'month' => $month,
                'department_id' => $departmentId,
                'q' => $search,
            ]);

            // Normalize rows to arrays for Excel
            $rows = array_map(function ($r) {
                if (is_object($r)) $r = (array)$r;
                $r['name'] = $r['name'] ?? ($r['employee_name'] ?? '-');
                $r['employee_code'] = $r['employee_code'] ?? ($r['code'] ?? '');
                $r['email'] = $r['email'] ?? ($r['official_email'] ?? ($r['employee_email'] ?? ''));
                return $r;
            }, $result['rows'] ?? []);

            $filename = 'attendance_monthly_' . ($month ?? now()->format('Y-m')) . '.xlsx';
            return Excel::download(
                new AttendanceMonthlyExport($rows, array_merge(['month' => $month], $result['filters'] ?? [])),
                $filename
            );
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate monthly Excel: ' . $e->getMessage(),
            ], 500);
        }
    }
}


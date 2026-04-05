<?php

namespace Modules\HRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HRM\Http\Resources\AssetAssignmentHistoryResource;
use Modules\HRM\Models\Asset;
use Modules\HRM\Models\AssetAssignmentHistory;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Modules\HRM\Exports\AssetAssignmentHistoriesExport;

class AssetAssignmentHistoryController extends Controller
{
    private function applyFilters($query, array $filters)
    {
        return $query
            ->when(!empty($filters['asset_id']), fn($q) => $q->where('asset_id', $filters['asset_id']))
            ->when(!empty($filters['employee_id']), fn($q) => $q->where('employee_id', $filters['employee_id']))
            ->when(isset($filters['returned']) && $filters['returned'] !== '', function ($q) use ($filters) {
                // returned=1 => returned_at not null, returned=0 => still assigned
                if ((int) $filters['returned'] === 1) return $q->whereNotNull('returned_at');
                if ((int) $filters['returned'] === 0) return $q->whereNull('returned_at');
                return $q;
            })
            ->when(!empty($filters['start_date']), fn($q) => $q->whereDate('assigned_date', '>=', $filters['start_date']))
            ->when(!empty($filters['end_date']), fn($q) => $q->whereDate('assigned_date', '<=', $filters['end_date']))
            ->when(!empty($filters['q']), function ($q) use ($filters) {
                $term = trim((string) $filters['q']);
                $q->where(function ($qq) use ($term) {
                    $qq->whereHas('employee', function ($qEmp) use ($term) {
                        $qEmp->where('name', 'ilike', "%{$term}%")
                            ->orWhere('official_email', 'ilike', "%{$term}%")
                            ->orWhere('personal_email', 'ilike', "%{$term}%");
                    })->orWhereHas('asset', function ($qAsset) use ($term) {
                        $qAsset->where('name', 'ilike', "%{$term}%")
                            ->orWhere('serial_no', 'ilike', "%{$term}%");
                    });
                });
            });
    }

    public function globalIndex(Request $request): JsonResponse
    {
        $filters = $request->only([
            'asset_id',
            'employee_id',
            'returned',
            'q',
            'start_date',
            'end_date',
            'per_page',
            'page',
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);
        if ($perPage === 0) $perPage = 10;

        $query = AssetAssignmentHistory::query()
            ->with(['employee', 'asset'])
            // Role-based visibility
            ->when(auth()->user()?->onlyEmployee(), function ($q) {
                $employeeId = auth()->user()?->employee?->id;
                if ($employeeId)
                    $q->where('employee_id', $employeeId);
                else
                    $q->whereRaw('1 = 0');
            })
            ->when(
                auth()->user()?->hasRole('Manager') && !auth()->user()?->hasRole(['Hr']),
                function ($q) {
                    $managerEmployeeId = auth()->user()?->employee?->id;

                    if (!$managerEmployeeId) {
                        $q->whereRaw('1 = 0');
                        return;
                    }

                    $q->where(function ($whereQuery) use ($managerEmployeeId) {
                        $whereQuery
                            ->where('employee_id', $managerEmployeeId)
                            ->orWhereHas('employee', fn($employeeQuery) =>
                                $employeeQuery->where('employees.reporting_to', $managerEmployeeId)
                            );
                    });
                }
            )
            ->tap(fn($q) => $this->applyFilters($q, $filters))
            ->orderByDesc('asset_id')
            ->orderByDesc('assigned_date')->orderByDesc('id');

        if ($perPage === -1) {
            $items = $query->get();
            return response()->json([
                'success' => true,
                'data' => AssetAssignmentHistoryResource::collection($items),
                'pagination' => [
                    'total' => $items->count(),
                    'per_page' => -1,
                    'current_page' => 1,
                    'last_page' => 1,
                ],
            ]);
        }

        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => AssetAssignmentHistoryResource::collection($paginator->items()),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function globalExportPdf(Request $request)
    {
        $filters = $request->only([
            'asset_id',
            'employee_id',
            'returned',
            'q',
            'start_date',
            'end_date',
        ]);

        $histories = AssetAssignmentHistory::query()
            ->with(['employee', 'asset'])
            // Role-based visibility
            ->when(auth()->user()?->onlyEmployee(), function ($q) {
                $employeeId = auth()->user()?->employee?->id;
                if ($employeeId)
                    $q->where('employee_id', $employeeId);
                else
                    $q->whereRaw('1 = 0');
            })
            ->when(
                auth()->user()?->hasRole('Manager') && !auth()->user()?->hasRole(['Hr']),
                function ($q) {
                    $managerEmployeeId = auth()->user()?->employee?->id;

                    if (!$managerEmployeeId) {
                        $q->whereRaw('1 = 0');
                        return;
                    }

                    $q->where(function ($whereQuery) use ($managerEmployeeId) {
                        $whereQuery
                            ->where('employee_id', $managerEmployeeId)
                            ->orWhereHas('employee', fn($employeeQuery) =>
                                $employeeQuery->where('employees.reporting_to', $managerEmployeeId)
                            );
                    });
                }
            )
            ->tap(fn($q) => $this->applyFilters($q, $filters))
            ->orderByDesc('id')
            ->get();

        $data = [
            'asset' => null,
            'histories' => AssetAssignmentHistoryResource::collection($histories)->resolve(),
            'filters' => $filters,
            'generated_at' => now()->format('d-m-Y H:i:s'),
            'total_records' => $histories->count(),
        ];

        $filename = 'asset_assignment_histories_' . now()->format('Ymd_His');

        $pdf = Pdf::loadView('hrm::assets.assignment-history-pdf', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download($filename . '.pdf');
    }

    public function globalExportExcel(Request $request)
    {
        $filters = $request->only([
            'asset_id',
            'employee_id',
            'returned',
            'q',
            'start_date',
            'end_date',
        ]);

        $histories = AssetAssignmentHistory::query()
            ->with(['employee', 'asset'])
            // Role-based visibility
            ->when(auth()->user()?->onlyEmployee(), function ($q) {
                $employeeId = auth()->user()?->employee?->id;
                if ($employeeId)
                    $q->where('employee_id', $employeeId);
                else
                    $q->whereRaw('1 = 0');
            })
            ->when(
                auth()->user()?->hasRole('Manager') && !auth()->user()?->hasRole(['Hr']),
                function ($q) {
                    $managerEmployeeId = auth()->user()?->employee?->id;

                    if (!$managerEmployeeId) {
                        $q->whereRaw('1 = 0');
                        return;
                    }

                    $q->where(function ($whereQuery) use ($managerEmployeeId) {
                        $whereQuery
                            ->where('employee_id', $managerEmployeeId)
                            ->orWhereHas('employee', fn($employeeQuery) =>
                                $employeeQuery->where('employees.reporting_to', $managerEmployeeId)
                            );
                    });
                }
            )
            ->tap(fn($q) => $this->applyFilters($q, $filters))
            ->orderByDesc('id')
            ->get();

        $data = [
            'histories' => AssetAssignmentHistoryResource::collection($histories)->resolve(),
            'filters' => $filters,
            'generated_at' => now()->format('d-m-Y H:i:s'),
            'total_records' => $histories->count(),
        ];

        $filename = 'asset_assignment_histories_' . now()->format('Ymd_His');

        return Excel::download(new AssetAssignmentHistoriesExport($data), $filename . '.xlsx');
    }

    public function index(Request $request, Asset $asset): JsonResponse
    {
        $filters = $request->only([
            'employee_id',
            'returned',
            'q',
            'start_date',
            'end_date',
            'per_page',
            'page',
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);
        if ($perPage === 0) $perPage = 10;

        $query = $asset->assignmentHistories()
            ->with('employee')
            // Role-based visibility
            ->when(auth()->user()?->onlyEmployee(), function ($q) {
                $employeeId = auth()->user()?->employee?->id;
                if ($employeeId)
                    $q->where('employee_id', $employeeId);
                else
                    $q->whereRaw('1 = 0');
            })
            ->when(
                auth()->user()?->hasRole('Manager') && !auth()->user()?->hasRole(['Hr']),
                function ($q) {
                    $managerEmployeeId = auth()->user()?->employee?->id;

                    if (!$managerEmployeeId) {
                        $q->whereRaw('1 = 0');
                        return;
                    }

                    $q->where(function ($whereQuery) use ($managerEmployeeId) {
                        $whereQuery
                            ->where('employee_id', $managerEmployeeId)
                            ->orWhereHas('employee', fn($employeeQuery) =>
                                $employeeQuery->where('employees.reporting_to', $managerEmployeeId)
                            );
                    });
                }
            )
            ->when(!empty($filters['employee_id']), fn($q) => $q->where('employee_id', $filters['employee_id']))
            ->when(isset($filters['returned']) && $filters['returned'] !== '', function ($q) use ($filters) {
                // returned=1 => returned_at not null, returned=0 => still assigned
                if ((int) $filters['returned'] === 1) return $q->whereNotNull('returned_at');
                if ((int) $filters['returned'] === 0) return $q->whereNull('returned_at');
                return $q;
            })
            ->when(!empty($filters['start_date']), fn($q) => $q->whereDate('assigned_date', '>=', $filters['start_date']))
            ->when(!empty($filters['end_date']), fn($q) => $q->whereDate('assigned_date', '<=', $filters['end_date']))
            ->when(!empty($filters['q']), function ($q) use ($filters) {
                $term = trim((string) $filters['q']);
                $q->whereHas('employee', function ($qq) use ($term) {
                    $qq->where('name', 'ilike', "%{$term}%")
                        ->orWhere('official_email', 'ilike', "%{$term}%")
                        ->orWhere('personal_email', 'ilike', "%{$term}%");
                });
            })
            ->orderByDesc('id');

        if ($perPage === -1) {
            $items = $query->get();
            return response()->json([
                'success' => true,
                'data' => AssetAssignmentHistoryResource::collection($items),
                'pagination' => [
                    'total' => $items->count(),
                    'per_page' => -1,
                    'current_page' => 1,
                    'last_page' => 1,
                ],
            ]);
        }

        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => AssetAssignmentHistoryResource::collection($paginator->items()),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function exportPdf(Request $request, Asset $asset)
    {
        $filters = $request->only([
            'employee_id',
            'returned',
            'q',
            'start_date',
            'end_date',
        ]);

        $histories = $asset->assignmentHistories()
            ->with('employee')
            // Role-based visibility
            ->when(auth()->user()?->onlyEmployee(), function ($q) {
                $employeeId = auth()->user()?->employee?->id;
                if ($employeeId)
                    $q->where('employee_id', $employeeId);
                else
                    $q->whereRaw('1 = 0');
            })
            ->when(
                auth()->user()?->hasRole('Manager') && !auth()->user()?->hasRole(['Hr']),
                function ($q) {
                    $managerEmployeeId = auth()->user()?->employee?->id;

                    if (!$managerEmployeeId) {
                        $q->whereRaw('1 = 0');
                        return;
                    }

                    $q->where(function ($whereQuery) use ($managerEmployeeId) {
                        $whereQuery
                            ->where('employee_id', $managerEmployeeId)
                            ->orWhereHas('employee', fn($employeeQuery) =>
                                $employeeQuery->where('employees.reporting_to', $managerEmployeeId)
                            );
                    });
                }
            )
            ->when(!empty($filters['employee_id']), fn($q) => $q->where('employee_id', $filters['employee_id']))
            ->when(isset($filters['returned']) && $filters['returned'] !== '', function ($q) use ($filters) {
                if ((int) $filters['returned'] === 1) return $q->whereNotNull('returned_at');
                if ((int) $filters['returned'] === 0) return $q->whereNull('returned_at');
                return $q;
            })
            ->when(!empty($filters['start_date']), fn($q) => $q->whereDate('assigned_date', '>=', $filters['start_date']))
            ->when(!empty($filters['end_date']), fn($q) => $q->whereDate('assigned_date', '<=', $filters['end_date']))
            ->when(!empty($filters['q']), function ($q) use ($filters) {
                $term = trim((string) $filters['q']);
                $q->whereHas('employee', function ($qq) use ($term) {
                    $qq->where('name', 'ilike', "%{$term}%")
                        ->orWhere('official_email', 'ilike', "%{$term}%")
                        ->orWhere('personal_email', 'ilike', "%{$term}%");
                });
            })
            ->orderByDesc('id')
            ->get();

        $data = [
            'asset' => $asset,
            'histories' => AssetAssignmentHistoryResource::collection($histories)->resolve(),
            'filters' => $filters,
            'generated_at' => now()->format('d-m-Y H:i:s'),
            'total_records' => $histories->count(),
        ];

        $filename = 'asset_assignment_history_' . $asset->id . '_' . now()->format('Ymd_His');

        $pdf = Pdf::loadView('hrm::assets.assignment-history-pdf', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download($filename . '.pdf');
    }

    public function exportExcel(Request $request, Asset $asset)
    {
        $filters = $request->only([
            'employee_id',
            'returned',
            'q',
            'start_date',
            'end_date',
        ]);

        $histories = $asset->assignmentHistories()
            ->with(['employee', 'asset'])
            // Role-based visibility
            ->when(auth()->user()?->onlyEmployee(), function ($q) {
                $employeeId = auth()->user()?->employee?->id;
                if ($employeeId)
                    $q->where('employee_id', $employeeId);
                else
                    $q->whereRaw('1 = 0');
            })
            ->when(
                auth()->user()?->hasRole('Manager') && !auth()->user()?->hasRole(['Hr']),
                function ($q) {
                    $managerEmployeeId = auth()->user()?->employee?->id;

                    if (!$managerEmployeeId) {
                        $q->whereRaw('1 = 0');
                        return;
                    }

                    $q->where(function ($whereQuery) use ($managerEmployeeId) {
                        $whereQuery
                            ->where('employee_id', $managerEmployeeId)
                            ->orWhereHas('employee', fn($employeeQuery) =>
                                $employeeQuery->where('employees.reporting_to', $managerEmployeeId)
                            );
                    });
                }
            )
            ->when(!empty($filters['employee_id']), fn($q) => $q->where('employee_id', $filters['employee_id']))
            ->when(isset($filters['returned']) && $filters['returned'] !== '', function ($q) use ($filters) {
                if ((int) $filters['returned'] === 1) return $q->whereNotNull('returned_at');
                if ((int) $filters['returned'] === 0) return $q->whereNull('returned_at');
                return $q;
            })
            ->when(!empty($filters['start_date']), fn($q) => $q->whereDate('assigned_date', '>=', $filters['start_date']))
            ->when(!empty($filters['end_date']), fn($q) => $q->whereDate('assigned_date', '<=', $filters['end_date']))
            ->when(!empty($filters['q']), function ($q) use ($filters) {
                $term = trim((string) $filters['q']);
                $q->whereHas('employee', function ($qq) use ($term) {
                    $qq->where('name', 'ilike', "%{$term}%")
                        ->orWhere('official_email', 'ilike', "%{$term}%")
                        ->orWhere('personal_email', 'ilike', "%{$term}%");
                });
            })
            ->orderByDesc('id')
            ->get();

        $data = [
            'histories' => AssetAssignmentHistoryResource::collection($histories)->resolve(),
            'filters' => $filters,
            'generated_at' => now()->format('d-m-Y H:i:s'),
            'total_records' => $histories->count(),
        ];

        $filename = 'asset_assignment_history_' . $asset->id . '_' . now()->format('Ymd_His');

        return Excel::download(new AssetAssignmentHistoriesExport($data), $filename . '.xlsx');
    }
}


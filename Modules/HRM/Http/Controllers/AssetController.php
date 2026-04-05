<?php

namespace Modules\HRM\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Modules\HRM\Models\Asset;
use Modules\HRM\Http\Requests\AssetRequest;
use Modules\HRM\Http\Resources\AssetResource;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Modules\HRM\Exports\AssetsExport;
use Modules\HRM\Contracts\AssetRepositoryInterface;

class AssetController extends Controller
{
    protected $assetRepository;

    public function __construct(AssetRepositoryInterface $assetRepository)
    {
        $this->assetRepository = $assetRepository;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'asset_type_id',
                'asset_assign_type',
                'employee_id',
                'q',
                'sort_by',
                'sort_order',
                'per_page'
            ]);

            // Map camelCase params (from frontend) to snake_case expected by repository
            if ($request->has('sortBy')) {
                $filters['sort_by'] = $request->input('sortBy');
            }
            if ($request->has('orderBy')) {
                $filters['sort_order'] = $request->input('orderBy');
            }

            $assets = $this->assetRepository->paginate($filters);

            return response()->json([
                'success' => true,
                'data' => AssetResource::collection($assets->items()),
                'pagination' => [
                    'current_page' => $assets->currentPage(),
                    'last_page' => $assets->lastPage(),
                    'per_page' => $assets->perPage(),
                    'total' => $assets->total(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch assets',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function listing(Request $request)
    {
        try {
            $assets = $this->assetRepository->list($request);

            return response()->json([
                'success' => true,
                'data' => AssetResource::collection($assets)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch assets',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AssetRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $asset = $this->assetRepository->create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Asset created successfully',
                'data' => new AssetResource($asset),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create asset',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Asset $asset): JsonResponse
    {
        try {
            $asset = $this->assetRepository->find($asset->id);

            return response()->json([
                'success' => true,
                'data' => new AssetResource($asset),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch asset',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AssetRequest $request, Asset $asset): JsonResponse
    {
        try {
            $validated = $request->validated();
            $asset = $this->assetRepository->update($asset->id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Asset updated successfully',
                'data' => new AssetResource($asset),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update asset',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Asset $asset): JsonResponse
    {
        try {
            $this->assetRepository->delete($asset->id);

            return response()->json([
                'success' => true,
                'message' => 'Asset deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete asset',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get asset types for dropdown.
     */
    public function getAssetTypes(): JsonResponse
    {
        try {
            $assetTypes = $this->assetRepository->getAssetTypes();

            return response()->json([
                'success' => true,
                'data' => $assetTypes,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch asset types',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get employees for dropdown.
     */
    public function getEmployees(): JsonResponse
    {
        try {
            $employees = $this->assetRepository->getEmployees();

            return response()->json([
                'success' => true,
                'data' => $employees,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch employees',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get assigned/unassigned asset counts
     */
    public function getCounts(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'asset_type_id',
                'employee_id',
                'q',
            ]);
            $counts = $this->assetRepository->getCounts($filters);
            return response()->json([
                'success' => true,
                'data' => $counts,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch asset counts',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function exportPdf(Request $request)
    {
        try {
            $filters = $request->all();
            $filters = $request->all();
            if (isset($filters['sortBy'])) $filters['sort_by'] = $filters['sortBy'];
            if (isset($filters['orderBy'])) $filters['sort_order'] = $filters['orderBy'];
            $filters['per_page'] = -1;

            $assets = $this->assetRepository->export($filters);

            $data = [
                'assets' => AssetResource::collection($assets)->toArray($request),
                'filters' => $filters,
                'generated_at' => now()->format('d-m-Y H:i:s'),
                'total_records' => count($assets),
            ];

            $filename = 'assets_report';
            if (!empty($filters['q'])) $filename .= '_' . $filters['q'];
            $filename .= '_' . now()->format('Ymd_His');

            $pdf = Pdf::loadView('hrm::assets.pdf-export', $data);
            $pdf->setPaper('A4', 'portrait');
            return $pdf->download($filename . '.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportExcel(Request $request)
    {
        try {
            $filters = $request->all();
            $filters = $request->all();
            if (isset($filters['sortBy'])) $filters['sort_by'] = $filters['sortBy'];
            if (isset($filters['orderBy'])) $filters['sort_order'] = $filters['orderBy'];
            $filters['per_page'] = -1;

            $assets = $this->assetRepository->export($filters);

            $data = [
                'assets' => $assets,
                'filters' => $filters,
                'generated_at' => now()->format('d-m-Y H:i:s'),
                'total_records' => count($assets),
            ];

            $filename = 'assets_report';
            if (!empty($filters['q'])) $filename .= '_' . $filters['q'];
            $filename .= '_' . now()->format('Ymd_His');

            return Excel::download(new AssetsExport($data), $filename . '.xlsx');
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate Excel: ' . $e->getMessage()
            ], 500);
        }
    }
}

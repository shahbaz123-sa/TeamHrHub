<?php

namespace Modules\HRM\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Modules\HRM\Models\AssetAttribute;
use Modules\HRM\Http\Resources\AssetAttributeResource;
use Modules\HRM\Http\Requests\StoreAssetAttributeRequest;
use Modules\HRM\Http\Requests\UpdateAssetAttributeRequest;
use Modules\HRM\Contracts\AssetAttributeRepositoryInterface;

class AssetAttributeController extends Controller
{
    protected $assetAttributeRepository;

    public function __construct(AssetAttributeRepositoryInterface $assetAttributeRepository)
    {
        $this->assetAttributeRepository = $assetAttributeRepository;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'asset_type_id', 
                'field_type', 
                'search', 
                'sort_by', 
                'sort_order', 
                'per_page'
            ]);

            $assetAttributes = $this->assetAttributeRepository->paginate($filters);

            return response()->json([
                'success' => true,
                'data' => AssetAttributeResource::collection($assetAttributes->items()),
                'pagination' => [
                    'current_page' => $assetAttributes->currentPage(),
                    'last_page' => $assetAttributes->lastPage(),
                    'per_page' => $assetAttributes->perPage(),
                    'total' => $assetAttributes->total(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch asset attributes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAssetAttributeRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $assetAttribute = $this->assetAttributeRepository->create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Asset attribute created successfully',
                'data' => new AssetAttributeResource($assetAttribute),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create asset attribute',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(AssetAttribute $assetAttribute): JsonResponse
    {
        try {
            $assetAttribute = $this->assetAttributeRepository->find($assetAttribute->id);

            return response()->json([
                'success' => true,
                'data' => new AssetAttributeResource($assetAttribute),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch asset attribute',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAssetAttributeRequest $request, AssetAttribute $assetAttribute): JsonResponse
    {
        try {
            $validated = $request->validated();
            $assetAttribute = $this->assetAttributeRepository->update($assetAttribute->id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Asset attribute updated successfully',
                'data' => new AssetAttributeResource($assetAttribute),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update asset attribute',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AssetAttribute $assetAttribute): JsonResponse
    {
        try {
            $this->assetAttributeRepository->delete($assetAttribute->id);

            return response()->json([
                'success' => true,
                'message' => 'Asset attribute deleted successfully',
            ]);
        } catch (\Exception $e) {
            $statusCode = $e->getMessage() === 'Cannot delete asset attribute that has values assigned to assets' ? 422 : 500;
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => $e->getMessage(),
            ], $statusCode);
        }
    }

    /**
     * Get asset types for dropdown.
     */
    public function getAssetTypes(): JsonResponse
    {
        try {
            $assetTypes = $this->assetAttributeRepository->getAssetTypes();

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
}

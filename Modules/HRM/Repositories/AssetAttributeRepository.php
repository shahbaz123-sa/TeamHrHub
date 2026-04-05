<?php

namespace Modules\HRM\Repositories;

use Modules\HRM\Models\AssetAttribute;
use Modules\HRM\Models\AssetType;
use Modules\HRM\Contracts\AssetAttributeRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\Paginator;

class AssetAttributeRepository implements AssetAttributeRepositoryInterface
{
    public function paginate(array $filters = []): Paginator
    {
        $query = AssetAttribute::with(['assetType'])
            ->when(isset($filters['asset_type_id']), function ($q) use ($filters) {
                $q->where('asset_type_id', $filters['asset_type_id']);
            })
            ->when(isset($filters['field_type']), function ($q) use ($filters) {
                $q->where('field_type', $filters['field_type']);
            })
            ->when(isset($filters['search']) && $filters['search'] !== '', function ($q) use ($filters) {
                $search = strtolower($filters['search']);
                $q->where(function ($where) use ($search) {
                    $where->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                          ->orWhereHas('assetType', function ($subQ) use ($search) {
                              $subQ->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
                          });
                });
            });

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function find($id)
    {
        return AssetAttribute::with(['assetType'])->findOrFail($id);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Handle options for select field type
            if ($data['field_type'] === 'select' && isset($data['options'])) {
                $data['options'] = is_array($data['options']) 
                    ? $data['options'] 
                    : json_decode($data['options'], true);
            } else {
                $data['options'] = null;
            }

            $assetAttribute = AssetAttribute::create($data);
            return $assetAttribute->load('assetType');
        });
    }

    public function update($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $assetAttribute = $this->find($id);

            // Handle options for select field type
            if ($data['field_type'] === 'select' && isset($data['options'])) {
                $data['options'] = is_array($data['options']) 
                    ? $data['options'] 
                    : json_decode($data['options'], true);
            } else {
                $data['options'] = null;
            }

            $assetAttribute->update($data);
            return $assetAttribute->load('assetType');
        });
    }

    public function delete($id)
    {
        return DB::transaction(function () use ($id) {
            $assetAttribute = $this->find($id);
            
            // Check if attribute has values
            if (!$this->canDelete($id)) {
                throw new \Exception('Cannot delete asset attribute that has values assigned to assets');
            }

            return $assetAttribute->delete();
        });
    }

    public function getAssetTypes()
    {
        return AssetType::select('id', 'name')->get();
    }

    public function canDelete($id): bool
    {
        $assetAttribute = $this->find($id);
        return !$assetAttribute->attributeValues()->exists();
    }
}

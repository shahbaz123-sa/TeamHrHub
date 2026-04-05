<?php

namespace Modules\HRM\Repositories;

use Modules\HRM\Models\Asset;
use Modules\HRM\Models\AssetType;
use Modules\HRM\Models\Employee;
use Modules\HRM\Models\AssetAttributeValue;
use Modules\HRM\Contracts\AssetRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\Paginator;

class AssetRepository implements AssetRepositoryInterface
{
    public function paginate(array $filters = []): Paginator
    {
        $query = Asset::with(['assetType', 'employee', 'attributeValues', 'employee.user', 'assignmentHistories'])
            // Role-based visibility
            ->when(auth()->user()?->onlyEmployee(), function ($q) {
                $employeeId = auth()->user()?->employee?->id;
                if ($employeeId)
                    $q->where('assign_to', $employeeId);
                else
                    // If employee record is missing, return no rows for safety
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
                            ->where('assign_to', $managerEmployeeId)
                            ->orWhereHas('employee', fn($employeeQuery) =>
                                $employeeQuery->where('employees.reporting_to', $managerEmployeeId)
                            );
                    });
                }
            )
            // Existing filters
            ->when(isset($filters['employee_id']), function ($q) use ($filters) {
                $q->where('assign_to', $filters['employee_id']);
            })
            ->when(isset($filters['asset_type_id']), function ($q) use ($filters) {
                $q->where('asset_type_id', $filters['asset_type_id']);
            })
            ->when(isset($filters['asset_assign_type']), function ($q) use ($filters) {
                if($filters['asset_assign_type'] == 2){
                    $q->whereNull('assign_to');
                }
                else if($filters['asset_assign_type'] == 1){
                    $q->whereNotNull('assign_to');
                }
            })
            ->when(isset($filters['q']) && $filters['q'] !== '', function ($q) use ($filters) {
                $search = strtolower($filters['q']);
                $q->where(function ($where) use ($search) {
                    $where->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(serial_no) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(description) LIKE ?', ["%{$search}%"])
                        ->orWhereHas('assetType', fn($sub) => $sub->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]))
                        ->orWhereHas('employee', function($sub) use ($search) {
                            $sub->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                                ->orWhereRaw('LOWER(personal_email) LIKE ?', ["%{$search}%"])
                                ->orWhereRaw('LOWER(official_email) LIKE ?', ["%{$search}%"])
                                ->orWhereRaw('LOWER(employee_code) LIKE ?', ["%{$search}%"]);
                        });
                 });
             });

        // Apply sorting. Support dot-notation for related columns like 'employee.name' or 'asset_types.name'
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        if (str_contains($sortBy, '.')) {
            [$prefix, $col] = explode('.', $sortBy, 2);
            // employee via assets.assign_to
            if (in_array($prefix, ['employee', 'employees'])) {
                $query->orderByRaw("(select {$col} from employees where employees.id = assets.assign_to) {$sortOrder}");
            } elseif (in_array($prefix, ['asset_types', 'asset_type'])) {
            $query->orderByRaw("(select {$col} from asset_types where asset_types.id = assets.asset_type_id) {$sortOrder}");
            } else {
                // fallback to simple order
                $query->orderBy($sortBy, $sortOrder);
            }
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Return all assets matching filters (no pagination) for exports
     */
    public function export(array $filters = [])
    {
        $query = Asset::with(['assetType', 'employee', 'attributeValues'])
            ->when(isset($filters['employee_id']), function ($q) use ($filters) {
                $q->where('assign_to', $filters['employee_id']);
            })
            ->when(isset($filters['asset_type_id']), function ($q) use ($filters) {
                $q->where('asset_type_id', $filters['asset_type_id']);
            })
            ->when(isset($filters['asset_assign_type']), function ($q) use ($filters) {
                if($filters['asset_assign_type'] == 2){
                    $q->whereNull('assign_to');
                }
                else if($filters['asset_assign_type'] == 1){
                    $q->whereNotNull('assign_to');
                }
            })
            ->when(isset($filters['q']) && $filters['q'] !== '', function ($q) use ($filters) {
                $search = strtolower($filters['q']);
                $q->where(function ($where) use ($search) {
                    $where->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(serial_no) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(description) LIKE ?', ["%{$search}%"])
                        ->orWhereHas('assetType', fn($sub) => $sub->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]))
                        ->orWhereHas('employee', function($sub) use ($search) {
                            $sub->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                                ->orWhereRaw('LOWER(personal_email) LIKE ?', ["%{$search}%"])
                                ->orWhereRaw('LOWER(official_email) LIKE ?', ["%{$search}%"])
                                ->orWhereRaw('LOWER(employee_code) LIKE ?', ["%{$search}%"]);
                        });
                 });
             });

        // Apply sorting if provided. Support related columns via subqueries.
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        if (str_contains($sortBy, '.')) {
            [$prefix, $col] = explode('.', $sortBy, 2);
            if (in_array($prefix, ['employee', 'employees'])) {
                $query->orderByRaw("(select {$col} from employees where employees.id = assets.assign_to) {$sortOrder}");
            } elseif (in_array($prefix, ['asset_types', 'asset_type'])) {
                $query->orderByRaw("(select {$col} from asset_types where asset_types.id = assets.asset_type_id) {$sortOrder}");
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

         return $query->get();
    }

    public function list($request)
    {
        $employeeId = $request->query('employee_id');
        $assets = Asset::with(['assetType', 'employee', 'attributeValues'])
            ->where(function ($query) use ($employeeId) {
                $query->whereNull('assign_to'); // unassigned
                if ($employeeId) {
                    $query->orWhere('assign_to', $employeeId); // include assets assigned to this employee
                }
            })
            ->orderBy('name', 'desc')
            ->get();
        return $assets;
    }

    public function find($id)
    {
        return Asset::with(['assetType', 'employee', 'attributeValues'])->findOrFail($id);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $attributes = $data['attributes'] ?? [];
            unset($data['attributes']); // Remove attributes from main asset data

            $asset = Asset::create($data);
            
            // Save asset attribute values
            if (!empty($attributes)) {
                $this->saveAttributeValues($asset->id, $attributes);
            }
            
            return $asset->load(['assetType', 'employee', 'attributeValues']);
        });
    }

    public function update($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $asset = $this->find($id);
            $attributes = $data['attributes'] ?? [];
            unset($data['attributes']); // Remove attributes from main asset data
            
            $asset->update($data);
            
            // Update asset attribute values
            if (isset($attributes)) {
                // Delete existing attribute values
                AssetAttributeValue::where('asset_id', $asset->id)->delete();
                
                // Save new attribute values
                $this->saveAttributeValues($asset->id, $attributes);
            }
            
            return $asset->load(['assetType', 'employee', 'attributeValues']);
        });
    }

    public function delete($id)
    {
        return DB::transaction(function () use ($id) {
            $asset = $this->find($id);
            
            // Delete associated attribute values
            AssetAttributeValue::where('asset_id', $asset->id)->delete();
            
            return $asset->delete();
        });
    }

    /**
     * Get asset types for dropdown
     */
    public function getAssetTypes()
    {
        return AssetType::select('id', 'name')->get();
    }

    /**
     * Get employees for dropdown
     */
    public function getEmployees()
    {
        return Employee::select('id', 'name')->get();
    }

    /**
     * Save attribute values for an asset
     */
    private function saveAttributeValues($assetId, array $attributes)
    {
        foreach ($attributes as $attributeId => $value) {
            if (!empty($value) || $value === false || $value === 0) {
                // Convert value to string for storage
                $stringValue = is_bool($value) ? ($value ? 'true' : 'false') : (string)$value;
                
                AssetAttributeValue::create([
                    'asset_id' => $assetId,
                    'attribute_id' => $attributeId,
                    'value' => $stringValue,
                ]);
            }
        }
    }

    public function getCounts(array $filters = []): array
    {
        $query = Asset::query();
        if (isset($filters['asset_type_id'])) {
            $query->where('asset_type_id', $filters['asset_type_id']);
        }
        if (isset($filters['employee_id'])) {
            $query->where('assign_to', $filters['employee_id']);
        }
        if (isset($filters['q']) && $filters['q'] !== '') {
            $search = strtolower($filters['q']);
            $query->where(function ($where) use ($search) {
                $where->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(serial_no) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(description) LIKE ?', ["%{$search}%"]);
            });
        }
        $assigned = (clone $query)->whereNotNull('assign_to')->count();
        $unassigned = (clone $query)->whereNull('assign_to')->count();
        return [
            'assigned' => $assigned,
            'unassigned' => $unassigned,
        ];
    }
}

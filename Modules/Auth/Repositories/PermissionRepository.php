<?php

namespace Modules\Auth\Repositories;

use Modules\Auth\Models\Permission;
use Illuminate\Database\Eloquent\Collection;
use Modules\Auth\Contracts\PermissionRepositoryInterface;

class PermissionRepository implements PermissionRepositoryInterface
{
    public function all(array $filters = []): Collection
    {
        return Permission::with('module:id,name')->when(isset($filters['search']), function ($q) use ($filters) {
            $q->where('name', 'ilike', "%{$filters['search']}%");
        })->get();
    }

    public function find($id)
    {
        return Permission::findOrFail($id);
    }

    public function create(array $data)
    {
        return Permission::create([
            'name' => $data['name'],
            'guard_name' => $data['guard_name'] ?? 'web',
        ]);
    }

    public function update($id, array $data)
    {
        $permission = $this->find($id);
        $permission->update([
            'name' => $data['name'],
            'guard_name' => $data['guard_name'] ?? 'web',
        ]);
        return $permission;
    }

    public function delete($id)
    {
        $permission = $this->find($id);
        return $permission->delete();
    }
}

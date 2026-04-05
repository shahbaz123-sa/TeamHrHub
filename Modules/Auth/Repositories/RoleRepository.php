<?php

namespace Modules\Auth\Repositories;

use Modules\HRM\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Eloquent\Collection;
use Modules\Auth\Contracts\RoleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class RoleRepository implements RoleRepositoryInterface
{
    public function all(array $filters = []): Collection
    {
        if (isset($filters['for_role_assignment'])) {
            $assignableRoleIds = DB::table('role_assignable_roles')
                ->whereIn('role_id', data_get(auth()->user(), 'roles', collect())->pluck('id'))
                ->pluck('assignable_role_id');

            return Role::whereIn('id', $assignableRoleIds)->with('permissions')->get();
        }

        return Role::with('permissions', 'assignableRoles')
            ->when(!auth()->user()->hasRole('Super Admin'), fn($query) => $query->where('name', '<>', 'Super Admin'))
            ->when(isset($filters['search']), function ($q) use ($filters) {
                $q->where('name', 'ilike', "%{$filters['search']}%");
            })
            ->get();
    }

    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return Role::with('assignableRoles:id')
            ->when(!auth()->user()->hasRole('Super Admin'), fn($query) => $query->where('name', '<>', 'Super Admin'))
            ->when(isset($filters['q']), function ($q) use ($filters) {
                $q->where('name', 'ilike', "%{$filters['q']}%");
            })
            ->paginate($filters['per_page'] ?? $perPage);
    }

    public function find(string $id)
    {
        return Role::with('permissions')->findOrFail($id);
    }

    public function create(array $data)
    {
        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => $data['guard_name'] ?? 'web',
        ]);

        if (isset($data['permissions'])) {
            $role->syncPermissions(collect($data['permissions'])->pluck('name'));
        }

        if (isset($data['allowed_role_ids'])) {
            $role->assignableRoles()->sync($data['allowed_role_ids']);
        }

        return $role->load('permissions');
    }

    public function update(string $id, array $data)
    {
        $role = $this->find($id);

        $role->update([
            'name' => $data['name'],
            'guard_name' => $data['guard_name'] ?? 'web',
        ]);

        if (isset($data['permissions'])) {
            $role->syncPermissions(collect($data['permissions'])->pluck('name'));
        }

        if (isset($data['allowed_role_ids'])) {
            $role->assignableRoles()->sync($data['allowed_role_ids']);
        }

        return $role->load(['permissions', 'assignableRoles']);
    }

    public function delete(string $id): bool
    {
        $role = $this->find($id);
        return (bool) $role->delete();
    }

    public function getPermissions(): array
    {
        return Permission::all()->map(function ($permission) {
            return [
                'id' => $permission->id,
                'name' => $permission->name,
                'guard_name' => $permission->guard_name,
                'created_at' => $permission->created_at,
                'updated_at' => $permission->updated_at,
            ];
        })->toArray();
    }
}

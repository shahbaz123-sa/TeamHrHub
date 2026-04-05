<?php

namespace Modules\Auth\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface PermissionRepositoryInterface
{
    /**
     * Get all permissions
     *
     * @param array $filters
     * @return Collection
     */
    public function all(array $filters = []): Collection;

    /**
     * Find a permission by ID
     *
     * @param int|string $id
     * @return \Spatie\Permission\Models\Permission
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function find($id);

    /**
     * Create a new permission
     *
     * @param array $data
     * @return \Spatie\Permission\Models\Permission
     */
    public function create(array $data);

    /**
     * Update a permission
     *
     * @param int|string $id
     * @param array $data
     * @return \Spatie\Permission\Models\Permission
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function update($id, array $data);

    /**
     * Delete a permission
     *
     * @param int|string $id
     * @return bool
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function delete($id);
}

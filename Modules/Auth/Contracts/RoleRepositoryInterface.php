<?php

namespace Modules\Auth\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface RoleRepositoryInterface
{
    public function all(array $filters = []): Collection;
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator;
    public function find(string $id);
    public function create(array $data);
    public function update(string $id, array $data);
    public function delete(string $id): bool;
    public function getPermissions(): array;
}

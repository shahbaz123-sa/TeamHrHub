<?php

namespace Modules\Auth\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use App\Models\User;

interface UserRepositoryInterface
{
    public function all(array $filters = []): Collection;
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator;
    public function find(string $id): ?User;
    public function create(array $data): User;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
    public function getStats(): array;
    // New methods for Vuexy requirements
    public function getRoles(): array;
    public function getPlans(): array;
    public function getStatuses(): array;
}

<?php

namespace Modules\HRM\Contracts;

use Illuminate\Contracts\Pagination\Paginator;

interface EmployeeRepositoryInterface
{
    public function paginate(array $filters = []): Paginator;
    public function getAll(array $filters = []);
    public function getByRules(array $filters = []);
    public function paginateWithRoles(array $filters = []): Paginator;
    public function getManagers($request): \Illuminate\Support\Collection;
    public function getRfqManagers(array $data): \Illuminate\Support\Collection;
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function restore($id);
    public function forceDelete($id);
    public function assignRoles($data);
    public function updateExemption($data);
    public function paginateAdmingHr(array $filters = []): Paginator;
}

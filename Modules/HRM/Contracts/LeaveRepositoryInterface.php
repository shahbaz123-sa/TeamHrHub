<?php

namespace Modules\HRM\Contracts;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\Request;
use Modules\HRM\Models\Leave;

interface LeaveRepositoryInterface
{
    public function paginate(array $filters = []): Paginator;
    public function create(array $data): Leave;
    public function update(int $id, array $data): Leave;
    public function delete(int $id): bool;
    public function find(int $id): Leave;
    public function approveRejectByManager(int $id, array $data): Leave;
    public function approveRejectByHr(int $id, array $data): Leave;
    public function getUserLeaveBalance(int $employeeId, int $year = null): array;
    public function export(array $filters = []);
    public function getAllLeaveBalances(array $filters = []): Paginator;

}

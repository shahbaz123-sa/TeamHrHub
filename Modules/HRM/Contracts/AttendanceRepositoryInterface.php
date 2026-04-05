<?php

namespace Modules\HRM\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\HRM\Models\Attendance;

interface AttendanceRepositoryInterface
{
    public function all(): Collection;

    public function paginate(int $perPage = 10): LengthAwarePaginator;

    public function find(int $id): ?Attendance;

    public function create(array $data): Attendance;

    public function update(int $id, array $data): bool;

    public function updateAttendance(int $id, array $data): ?Attendance;

    public function delete(int $id): bool;

    public function checkIn(int $employeeId, array $data): Attendance;

    public function checkOut(int $employeeId, array $data): Attendance;

    public function getEmployeeAttendance(int $employeeId, array $filters = []): LengthAwarePaginator;

    public function getAttendanceByDateRange(array $filters, $isMailing = false): LengthAwarePaginator;

    public function getAttendanceForReport(array $filters): array;

    public function importAttendances(array $data): bool;

    public function exportAttendances(array $filters): array;
    public function getAttendanceStats(array $filters = []): array;
    public function syncAttendanceStatusForLeave($leave, $who, $status): bool;
    public function exportPdfDepartmentBelow($request, $isMailing = false);
    public function getAttendanceWeeklyReport(array $filters = [], $user = null);
}

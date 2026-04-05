<?php

namespace Modules\HRM\Repositories;

use Modules\HRM\Contracts\EmploymentStatusRepositoryInterface;
use Modules\HRM\Models\EmploymentStatus;

class EmploymentStatusRepository implements EmploymentStatusRepositoryInterface
{
    public function all()
    {
        return EmploymentStatus::latest('created_at')->get();
    }
    public function find($id)
    {
        return EmploymentStatus::find($id);
    }
    public function create(array $data)
    {
        return EmploymentStatus::create($data);
    }
    public function update($id, array $data)
    {
        $status = EmploymentStatus::find($id);
        if ($status) {
            $status->update($data);
        }
        return $status;
    }
    public function delete($id)
    {
        $status = EmploymentStatus::find($id);
        if ($status) {
            $status->delete();
        }
        return $status;
    }
}

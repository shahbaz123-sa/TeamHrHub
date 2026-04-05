<?php

namespace Modules\HRM\Repositories;

use Modules\HRM\Contracts\EmploymentTypeRepositoryInterface;
use Modules\HRM\Models\EmploymentType;

class EmploymentTypeRepository implements EmploymentTypeRepositoryInterface
{
    public function all()
    {
        return EmploymentType::latest('created_at')->get();
    }
    public function find($id)
    {
        return EmploymentType::find($id);
    }
    public function create(array $data)
    {
        return EmploymentType::create($data);
    }
    public function update($id, array $data)
    {
        $type = EmploymentType::find($id);
        if ($type) {
            $type->update($data);
        }
        return $type;
    }
    public function delete($id)
    {
        $type = EmploymentType::find($id);
        if ($type) {
            $type->delete();
        }
        return $type;
    }
}

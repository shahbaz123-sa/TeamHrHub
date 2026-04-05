<?php

namespace Modules\CRM\Repositories;

use Modules\CRM\Models\Supplier;
use Modules\CRM\Contracts\SupplierRepositoryInterface;

class SupplierRepository implements SupplierRepositoryInterface
{
    protected $model;

    public function __construct(Supplier $model)
    {
        $this->model = $model;
    }

    public function paginate(array $filters = [])
    {
        return $this->model
            ->when(isset($filters['q']), function ($query) use ($filters) {
                $query->whereAny(['name', 'email', 'phone', 'address', 'brand', 'product_category'], 'ilike', '%' . $filters['q'] . '%');
            })
            ->when(isset($filters['type']), function ($query) use ($filters) {
                $query->where('type', $filters['type']);
            })
            ->orderBy(
                data_get($filters, 'sort_by', 'id'),
                data_get($filters, 'order', 'desc')
            )
            ->paginate($filters['per_page'] ?? 10);
    }

    public function find(int $id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        // 
    }

    public function update(int $id, array $data)
    {
        // 
    }

    public function delete(int $id)
    {
        // 
    }
}

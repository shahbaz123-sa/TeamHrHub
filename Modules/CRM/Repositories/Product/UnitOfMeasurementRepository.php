<?php

namespace Modules\CRM\Repositories\Product;

use Modules\CRM\Models\Product\UnitOfMeasurement;
use Modules\CRM\Contracts\Product\UnitOfMeasurementRepositoryInterface;

class UnitOfMeasurementRepository implements UnitOfMeasurementRepositoryInterface
{
    protected $model;

    public function __construct(UnitOfMeasurement $model)
    {
        $this->model = $model;
    }

    public function paginate(array $filters = [])
    {
        return $this->model
            ->when(isset($filters['q']), function ($query) use ($filters) {
                $query->whereAny(['name', 'slug'], 'ilike', "%{$filters['q']}%");
            })
            ->when(isset($filters['for_attachment']), function ($query) use ($filters) {
                $query->where('is_active', true);
            })
            ->when(isset($filters['status']), function ($query) use ($filters) {
                $query->where('is_active', (bool)$filters['status']);
            })
            ->orderBy(
                data_get($filters, 'sort_by', 'updated_at'),
                data_get($filters, 'order_by', 'desc')
            )
            ->paginate($filters['per_page'] ?? 10);
    }

    public function find(int $id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data)
    {
        $uom = $this->find($id);
        $uom->update($data);
        return $uom;
    }

    public function delete(int $id)
    {
        $this->find($id)->delete();
    }
}

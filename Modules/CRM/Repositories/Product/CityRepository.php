<?php

namespace Modules\CRM\Repositories\Product;

use Modules\CRM\Models\Product\City;
use Modules\CRM\Contracts\Product\CityRepositoryInterface;

class CityRepository implements CityRepositoryInterface
{
    protected $model;

    public function __construct(City $model)
    {
        $this->model = $model;
    }

    public function paginate(array $filters = [])
    {
        return $this->model
            ->when(isset($filters['q']), function ($query) use ($filters) {
                $query->whereAny(['name', 'slug'], 'ilike', "%{$filters['q']}%");
            })
            ->when(isset($filters['status']), function ($query) use ($filters) {
                $query->where('is_active', (bool)$filters['status']);
            })
            ->orderBy(
                data_get($filters, 'sort_by', 'updated_at'),
                data_get($filters, 'order_by', 'desc')
            )
            ->paginate(data_get($filters, 'per_page', 10));
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
        $city = $this->find($id);
        $city->update($data);
        return $city;
    }

    public function delete(int $id)
    {
        $city = $this->find($id);
        $city->delete();
    }
}

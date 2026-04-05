<?php

namespace Modules\CRM\Repositories\Product\Attribute;

use Modules\CRM\Traits\File\FileManager;
use Modules\CRM\Models\Product\Attribute\Value;
use Modules\CRM\Contracts\Product\Attribute\ValueRepositoryInterface;

class ValueRepository implements ValueRepositoryInterface
{
    use FileManager;

    protected $model;

    public function __construct(Value $model)
    {
        $this->model = $model;
    }

    public function paginate(array $filters = [])
    {
        return $this->model->with('attribute')
            ->when(isset($filters['q']), function ($query) use ($filters) {
                $query->whereAny(['name', 'slug'], 'ilike', "%{$filters['q']}%");
            })
            ->when(isset($filters['attribute_id']), function ($query) use ($filters) {
                $query->where('attribute_id', $filters['attribute_id']);
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
        $value = $this->find($id);
        $value->update($data);
        return $value;
    }

    public function delete(int $id)
    {
        $this->find($id)->delete();
    }
}

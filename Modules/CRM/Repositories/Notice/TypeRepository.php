<?php

namespace Modules\CRM\Repositories\Notice;

use Modules\CRM\Models\Notice\Type;
use Modules\CRM\Contracts\Notice\TypeRepositoryInterface;

class TypeRepository implements TypeRepositoryInterface
{
    protected $model;

    public function __construct(Type $model)
    {
        $this->model = $model;
    }

    public function paginate(array $filters = [])
    {
        return $this->model
            ->when(isset($filters['q']), function ($query) use ($filters) {
                $query->where('title', 'ilike', "%{$filters['q']}%");
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
        $item = $this->find($id);
        $item->update($data);
        return $item;
    }

    public function delete(int $id)
    {
        $item = $this->find($id);
        $item->delete();
    }
}

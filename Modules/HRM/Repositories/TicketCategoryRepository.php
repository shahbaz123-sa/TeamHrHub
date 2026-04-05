<?php

namespace Modules\HRM\Repositories;

use Modules\HRM\Contracts\TicketCategoryRepositoryInterface;
use Modules\HRM\Models\TicketCategory;

class TicketCategoryRepository implements TicketCategoryRepositoryInterface
{
    protected $model;

    public function __construct(TicketCategory $model)
    {
        $this->model = $model;
    }

    public function getAll()
    {
        return $this->model->latest('created_at')->get();
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data)
    {
        $category = $this->find($id);
        $category->update($data);
        return $category;
    }

    public function delete(int $id)
    {
        return $this->find($id)->delete();
    }

    public function find(int $id)
    {
        return $this->model->findOrFail($id);
    }
}

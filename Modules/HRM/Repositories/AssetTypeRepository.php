<?php

namespace Modules\HRM\Repositories;

use Modules\HRM\Contracts\AssetTypeRepositoryInterface;
use Modules\HRM\Models\AssetType;

class AssetTypeRepository implements AssetTypeRepositoryInterface
{
    protected $model;

    public function __construct(AssetType $model)
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

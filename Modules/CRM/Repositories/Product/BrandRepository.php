<?php

namespace Modules\CRM\Repositories\Product;

use Modules\CRM\Models\Product\Brand;
use Modules\CRM\Contracts\Product\BrandRepositoryInterface;
use Modules\CRM\Traits\File\FileManager;

class BrandRepository implements BrandRepositoryInterface
{
    use FileManager;

    protected $model;

    public function __construct(Brand $model)
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
            ->paginate($filters['per_page'] ?? 10);
    }

    public function find(int $id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        if (isset($data['image'])) {
            $fileName = $data['image']->getClientOriginalName();
            $data['image'] = $this->uploadFile(
                $data['image'],
                "product/brand/{$data['slug']}",
                $fileName
            );
        }

        return $this->model->create($data);
    }

    public function update(int $id, array $data)
    {
        $brand = $this->find($id);

        if (isset($data['image']) && !is_string($data['image'])) {

            if (!empty($brand->image)) $this->deleteFile($brand->image);

            $fileName = $data['image']->getClientOriginalName();
            $data['image'] = $this->uploadFile(
                $data['image'],
                "product/brand/{$data['slug']}",
                $fileName
            );
        } else {
            unset($data['image']);
        }

        $brand->update($data);
        return $brand;
    }

    public function delete(int $id)
    {
        $brand = $this->find($id);
        if (!empty($brand->image)) $this->deleteFile($brand->image);
        $brand->delete();
    }
}

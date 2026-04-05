<?php

namespace Modules\CRM\Repositories\Product;

use Modules\CRM\Models\Product\Category;
use Modules\CRM\Contracts\Product\CategoryRepositoryInterface;
use Modules\CRM\Traits\File\FileManager;

class CategoryRepository implements CategoryRepositoryInterface
{
    use FileManager;

    protected $model;

    public function __construct(Category $model)
    {
        $this->model = $model;
    }

    public function paginate(array $filters = [])
    {
        return $this->applyFilter($this->model->with('parent'), $filters)
            ->orderBy(
                data_get($filters, 'sort_by', 'updated_at'),
                data_get($filters, 'order_by', 'desc')
            )
            ->paginate($filters['per_page'] ?? 10);
    }

    public function getCategoryParents(array $filters)
    {
        return $this->applyFilter($this->model, $filters)
            ->whereNull('parent_id')
            ->orderBy(
                data_get($filters, 'sort_by', 'updated_at'),
                data_get($filters, 'order_by', 'desc')
            )
            ->get();
    }

    public function applyFilter($query, $filters)
    {
        $forId = data_get($filters, 'for', 0);

        return $query
            ->when(isset($filters['q']) || isset($filters['with_id']), function ($query) use ($filters) {
                if (isset($filters['q']) && isset($filters['with_id'])) {
                    $query->where(function ($where) use ($filters) {
                        $where->whereIn('id', explode(",", $filters['with_id']))
                            ->orWhereAny(['name', 'slug'], 'ilike', "%{$filters['q']}%");
                    });
                } elseif (isset($filters['q'])) {
                    $query->whereAny(['name', 'slug'], 'ilike', "%{$filters['q']}%");
                } elseif (isset($filters['with_id'])) {
                    $query->whereIn('id', explode(",", $filters['with_id']));
                }
            })
            ->when(isset($filters['id']), function ($query) use ($filters) {
                $query->where('id', $filters['id']);
            })
            ->when(isset($filters['parent_id']), function ($query) use ($filters) {
                $query->where('parent_id', $filters['parent_id']);
            })
            ->when(isset($filters['status']), function ($query) use ($filters) {
                $query->where('is_active', (bool)$filters['status']);
            })
            ->when(isset($filters['with_parent']), function ($query) use ($filters) {
                $query->with('parent:id,name');
            })
            ->when(isset($forId) && $forId > 0, function ($query) use ($forId) {
                $query->where('id', '<>', $forId)
                    ->where('parent_id', '<>', $forId)
                    ->where('is_active', true);
            });
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
                "product/category/{$data['slug']}",
                $fileName
            );
        }

        return $this->model->create($data);
    }

    public function update(int $id, array $data)
    {
        $category = $this->find($id);

        if (isset($data['image']) && !is_string($data['image'])) {

            if (!empty($category->image)) $this->deleteFile($category->image);

            $fileName = $data['image']->getClientOriginalName();
            $data['image'] = $this->uploadFile(
                $data['image'],
                "product/category/{$data['slug']}",
                $fileName
            );
        } else {
            unset($data['image']);
        }

        $category->update($data);
        return $category;
    }

    public function delete(int $id)
    {
        $category = $this->find($id);
        if (!empty($category->image)) $this->deleteFile($category->image);
        $category->delete();
    }
}

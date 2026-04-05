<?php

namespace Modules\HRM\Repositories;

use Modules\HRM\Contracts\CompanyPolicyRepositoryInterface;
use Modules\HRM\Models\CompanyPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\Paginator;
use Modules\HRM\Traits\File\FileManager;

class CompanyPolicyRepository implements CompanyPolicyRepositoryInterface
{
    use FileManager;

    protected $model;

    public function __construct(CompanyPolicy $model)
    {
        $this->model = $model;
    }

    public function all(array $filters = [])
    {
        $query = $this->model
            ->when(!empty($filters['q']), function ($q) use ($filters) {
                $q->where(function ($builder) use ($filters) {
                    $builder->whereAny(['title', 'description'], 'ilike', "%{$filters['q']}%");
                });
            })
            ->orderBy('display_order');

        return $query->get();
    }

    public function paginate(array $filters = []): Paginator
    {
        return $this->model
            ->with('branch')
            ->when(!empty($filters['q']), function ($q) use ($filters) {
                $q->where(function ($builder) use ($filters) {
                    $builder->whereAny(['title', 'description'], 'ilike', "%{$filters['q']}%");
                });
            })
            ->orderBy($filters['sortBy'] ?? 'display_order', ($filters['orderBy'] ?? 'asc') === 'desc' ? 'desc' : 'asc')
            ->paginate($filters['per_page'] ?? 10);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            if (isset($data['attachment'])) {
                $data['attachment_path'] = $this->uploadFile($data['attachment'], 'policies');
                unset($data['attachment']);
            }
            return $this->model->create($data);
        });
    }

    public function update(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $policy = $this->find($id);
            if (isset($data['attachment'])) {
                if ($policy->attachment_path) {
                    $this->deleteFile($policy->attachment_path);
                }
                $data['attachment_path'] = $this->uploadFile($data['attachment'], 'policies');
                unset($data['attachment']);
            }
            $policy->update($data);
            return $policy;
        });
    }

    public function delete(int $id)
    {
        return DB::transaction(function () use ($id) {
            $policy = $this->find($id);
            if ($policy->attachment_path) {
                $this->deleteFile($policy->attachment_path);
            }
            return (bool) $policy->delete();
        });
    }

    public function find(int $id)
    {
        return $this->model->findOrFail($id);
    }
}

<?php

namespace Modules\HRM\Repositories;

use Modules\HRM\Models\DocumentType;
use Modules\HRM\Contracts\DocumentTypeRepositoryInterface;

class DocumentTypeRepository implements DocumentTypeRepositoryInterface
{
    protected $model;

    public function __construct(DocumentType $model)
    {
        $this->model = $model;
    }

    public function getAll(array $filters = [])
    {
        return $this->model
            ->when(isset($filters['q']), function ($query) use ($filters) {
                $query->where('name', 'ilike', "%{$filters['q']}%");
            })
            ->orderByDesc('is_default')
            ->orderByRaw('CASE WHEN is_default = true THEN "order" END ASC')
            ->orderBy('name')
            ->get();
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
        $documentType = $this->find($id);
        $documentType->update($data);
        return $documentType;
    }

    public function delete(int $id)
    {
        return $this->find($id)->delete();
    }
}

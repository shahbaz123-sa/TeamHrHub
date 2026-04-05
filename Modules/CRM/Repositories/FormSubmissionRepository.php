<?php

namespace Modules\CRM\Repositories;

use Modules\CRM\Contracts\FormSubmissionRepositoryInterface;
use Modules\CRM\Models\FormSubmission;

class FormSubmissionRepository implements FormSubmissionRepositoryInterface
{
    protected $model;

    public function __construct(FormSubmission $model)
    {
        $this->model = $model;
    }

    public function paginate(array $filters = [])
    {
        return $this->model
            ->with(['category', 'product'])
            ->when(isset($filters['q']), function ($query) use ($filters) {
                $query->whereAny([
                    'full_name',
                    'email',
                    'phone',
                    'city',
                    'company_name',
                    'commodity',
                ], 'ilike', '%' . $filters['q'] . '%');
            })
            ->when(isset($filters['form_type']), function ($query) use ($filters) {
                $query->where('form_type', $filters['form_type']);
            })
            ->orderBy(
                data_get($filters, 'sort_by', 'id'),
                data_get($filters, 'order', 'desc')
            )
            ->paginate($filters['per_page'] ?? 10);
    }

    public function find(int $id)
    {
        return $this->model->with(['category', 'product'])->findOrFail($id);
    }
}

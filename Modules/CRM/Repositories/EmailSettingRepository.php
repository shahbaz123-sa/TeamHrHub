<?php

namespace Modules\CRM\Repositories;

use Modules\CRM\Models\EmailSetting;
use Modules\CRM\Contracts\EmailSettingRepositoryInterface;

class EmailSettingRepository implements EmailSettingRepositoryInterface
{
    protected $model;

    public function __construct(EmailSetting $model)
    {
        $this->model = $model;
    }

    public function paginate(array $filters = [])
    {
        return $this->model
            ->when(isset($filters['q']), function ($query) use ($filters) {
                $query->whereAny(['notify_on', 'recipients'], 'ilike', "%{$filters['q']}%");
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
        $emailSetting = $this->find($id);
        $emailSetting->update($data);
        return $emailSetting;
    }

    public function delete(int $id)
    {
        $emailSetting = $this->find($id);
        $emailSetting->delete();
    }
}

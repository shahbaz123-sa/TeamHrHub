<?php

namespace Modules\CRM\Repositories\Report;

use Modules\CRM\Models\Report\LatestReport;
use Modules\CRM\Contracts\Report\LatestReportRepositoryInterface;
use Modules\CRM\Traits\File\FileManager;

class LatestReportRepository implements LatestReportRepositoryInterface
{
    use FileManager;

    protected $model;

    public function __construct(LatestReport $model)
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
            ->latest('updated_at')
            ->paginate($filters['per_page'] ?? 10);
    }

    public function find(int $id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        if (isset($data['attachment'])) {
            $data['attachment'] = $this->uploadFile(
                $data['attachment'],
                "reports/latest_reports/" . $data['report_date'],
                $data['attachment']->getClientOriginalName()
            );
        }
        return $this->model->create($data);
    }

    public function update(int $id, array $data)
    {
        $item = $this->find($id);

        if (isset($data['attachment']) && !is_string($data['attachment'])) {

            if (!empty($item->attachment)) $this->deleteFile($item->attachment);

            $data['attachment'] = $this->uploadFile(
                $data['attachment'],
                "reports/latest_reports/" . $data['report_date'],
                $data['attachment']->getClientOriginalName()
            );
        } else {
            unset($data['attachment']);
        }

        $item->update($data);
        return $item;
    }

    public function delete(int $id)
    {
        $item = $this->find($id);
        if (!empty($item->attachment)) $this->deleteFile($item->attachment);
        $item->delete();
    }
}

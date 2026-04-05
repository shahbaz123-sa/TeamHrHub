<?php

namespace Modules\CRM\Repositories\Report;

use Modules\CRM\Models\Report\FinancialReport;
use Modules\CRM\Contracts\Report\FinancialReportRepositoryInterface;
use Modules\CRM\Traits\File\FileManager;

class FinancialReportRepository implements FinancialReportRepositoryInterface
{
    use FileManager;

    private $files = [
        'press_release',
        'financial_report',
        'presentation',
        'transcript',
        'video',
    ];

    protected $model;

    public function __construct(FinancialReport $model)
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
        foreach ($this->files as $file) {
            if (isset($data[$file])) {
                $data[$file] = $this->uploadFile(
                    $data[$file],
                    "reports/financial_reports/" . $data['report_date'] . "/$file",
                    $data[$file]->getClientOriginalName()
                );
            }
        }

        return $this->model->create($data);
    }

    public function update(int $id, array $data)
    {
        $item = $this->find($id);

        foreach ($this->files as $file) {
            if (isset($data[$file]) && !is_string($data[$file])) {

                if (!empty($item->{$file})) $this->deleteFile($item->{$file});

                $data[$file] = $this->uploadFile(
                    $data[$file],
                    "reports/financial_reports/" . $data['report_date'] . "/$file",
                    $data[$file]->getClientOriginalName()
                );
            } else {
                unset($data[$file]);
            }
        }

        $item->update($data);
        return $item;
    }

    public function delete(int $id)
    {
        $item = $this->find($id);

        foreach ($this->files as $file) {
            if (!empty($item->{$file})) $this->deleteFile($item->{$file});
        }

        $item->delete();
    }
}

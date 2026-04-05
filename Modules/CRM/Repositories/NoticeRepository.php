<?php

namespace Modules\CRM\Repositories;

use Modules\CRM\Models\Notice;
use Modules\CRM\Contracts\NoticeRepositoryInterface;
use Modules\CRM\Traits\File\FileManager;

class NoticeRepository implements NoticeRepositoryInterface
{
    use FileManager;

    private $files = [
        'pdf_attachment',
        'excel_attachment',
    ];

    protected $model;

    public function __construct(Notice $model)
    {
        $this->model = $model;
    }

    public function paginate(array $filters = [])
    {
        return $this->model->with('type')
            ->when(isset($filters['q']), function ($query) use ($filters) {
                $query->where('title', 'ilike', "%{$filters['q']}%");
            })
            ->when(isset($filters['status']), function ($query) use ($filters) {
                $query->where('is_active', (bool)$filters['status']);
            })
            ->when(isset($filters['type']), function ($query) use ($filters) {
                $query->where('type_id', $filters['type']);
            })
            ->latest('updated_at')
            ->paginate($filters['per_page'] ?? 10);
    }

    public function find(int $id)
    {
        return $this->model->with('type')->findOrFail($id);
    }

    public function create(array $data)
    {
        foreach ($this->files as $file) {
            if (isset($data[$file]) && $data[$file] instanceof \Illuminate\Http\UploadedFile) {
                $data[$file] = $this->uploadFile(
                    $data[$file],
                    "notices/" . ($data['year'] ?? date('Y')) . "/$file",
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
            if (isset($data[$file]) && $data[$file] instanceof \Illuminate\Http\UploadedFile) {
                if (!empty($item->{$file})) $this->deleteFile($item->{$file});

                $data[$file] = $this->uploadFile(
                    $data[$file],
                    "notices/" . ($data['year'] ?? $item->year) . "/$file",
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

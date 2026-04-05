<?php

namespace Modules\HRM\Repositories;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\HRM\Models\LeaveType;
use Modules\HRM\Contracts\LeaveTypeRepositoryInterface;

class LeaveTypeRepository implements LeaveTypeRepositoryInterface
{
    protected $model;

    public function __construct(LeaveType $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function find(int $id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        // If sort_order isn't provided, append to the end.
        if (!array_key_exists('sort_order', $data)) {
            $max = (int) $this->model->max('sort_order');
            $data['sort_order'] = $max + 1;
        }

        return $this->model->create($data);
    }

    public function update(int $id, array $data)
    {
        $record = $this->model->findOrFail($id);
        $record->update($data);
        return $record;
    }

    public function delete(int $id)
    {
        $this->model->findOrFail($id)->delete();
    }

    public function move(int $id, string $direction)
    {
        if (!in_array($direction, ['up', 'down'], true)) {
            throw new InvalidArgumentException('Invalid direction');
        }

        return DB::transaction(function () use ($id, $direction) {
            /** @var LeaveType $current */
            $current = $this->model->newQuery()->lockForUpdate()->findOrFail($id);

            // Pick neighbor in current ordered list (sort_order, id).
            $neighborQuery = $this->model->newQuery()->lockForUpdate();
            if ($direction === 'up') {
                $neighborQuery
                    ->where(function ($q) use ($current) {
                        $q->where('sort_order', '<', $current->sort_order)
                            ->orWhere(function ($q2) use ($current) {
                                $q2->where('sort_order', '=', $current->sort_order)
                                    ->where('id', '<', $current->id);
                            });
                    })
                    ->orderBy('sort_order', 'desc')
                    ->orderBy('id', 'desc');
            } else {
                $neighborQuery
                    ->where(function ($q) use ($current) {
                        $q->where('sort_order', '>', $current->sort_order)
                            ->orWhere(function ($q2) use ($current) {
                                $q2->where('sort_order', '=', $current->sort_order)
                                    ->where('id', '>', $current->id);
                            });
                    })
                    ->orderBy('sort_order', 'asc')
                    ->orderBy('id', 'asc');
            }

            /** @var LeaveType|null $neighbor */
            $neighbor = $neighborQuery->first();

            // Already at boundary.
            if (!$neighbor) {
                return $current;
            }

            $tmp = $current->sort_order;
            $current->sort_order = $neighbor->sort_order;
            $neighbor->sort_order = $tmp;

            $current->save();
            $neighbor->save();

            return $current->fresh();
        });
    }
}

<?php
namespace Modules\HRM\Contracts;

interface LeaveTypeRepositoryInterface
{
    public function all();
    public function find(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);

    /**
     * Move a leave type relative to its neighbors.
     *
     * @param  int  $id
     * @param  'up'|'down'  $direction
     */
    public function move(int $id, string $direction);
}

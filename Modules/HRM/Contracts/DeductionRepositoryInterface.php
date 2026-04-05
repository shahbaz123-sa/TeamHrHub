<?php

namespace Modules\HRM\Contracts;

interface DeductionRepositoryInterface
{
    public function getAll(array $filters);
    public function find(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
}

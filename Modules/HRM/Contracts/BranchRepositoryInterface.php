<?php

namespace Modules\HRM\Contracts;

interface BranchRepositoryInterface
{
    public function getAll(array $filters);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
    public function find(int $id);
    public function allowRemote();
    public function disallowRemote();
}

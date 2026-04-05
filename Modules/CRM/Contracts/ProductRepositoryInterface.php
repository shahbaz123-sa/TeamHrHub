<?php

namespace Modules\CRM\Contracts;

interface ProductRepositoryInterface
{
    public function paginate();
    public function getParents(array $data);
    public function find(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
}

<?php

namespace Modules\CRM\Contracts\Product;

interface BrandRepositoryInterface
{
    public function paginate();
    public function find(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
}

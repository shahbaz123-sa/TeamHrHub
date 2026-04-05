<?php

namespace Modules\CRM\Contracts\Product;

interface CategoryRepositoryInterface
{
    public function paginate();
    public function getCategoryParents(array $data);
    public function find(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
}

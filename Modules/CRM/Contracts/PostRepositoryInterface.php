<?php

namespace Modules\CRM\Contracts;

interface PostRepositoryInterface
{
    public function paginate(array $filters = []);
    public function find(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
}

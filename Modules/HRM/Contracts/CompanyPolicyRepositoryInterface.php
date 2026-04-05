<?php

namespace Modules\HRM\Contracts;

interface CompanyPolicyRepositoryInterface
{
    public function all(array $filters = []);
    public function paginate(array $filters = []);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
    public function find(int $id);
}

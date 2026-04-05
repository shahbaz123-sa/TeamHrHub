<?php

namespace Modules\HRM\Contracts;

use Illuminate\Contracts\Pagination\Paginator;

interface AssetRepositoryInterface
{
    public function paginate(array $filters = []): Paginator;
    public function list($request);
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function getAssetTypes();
    public function getEmployees();
}

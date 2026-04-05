<?php

namespace Modules\HRM\Contracts;

use Illuminate\Contracts\Pagination\Paginator;

interface AssetAttributeRepositoryInterface
{
    public function paginate(array $filters = []): Paginator;
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function getAssetTypes();
    public function canDelete($id): bool;
}

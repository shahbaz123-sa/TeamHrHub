<?php

namespace Modules\CRM\Contracts\Product;

use Illuminate\Http\UploadedFile;

interface DailyPriceRepositoryInterface
{
    public function paginate(array $filters = []);
    public function find(int $id);
    public function import(UploadedFile $file, array $data);
    public function export($batchId, array $data);
    public function getFilters();
    public function getProducts(array $filters = []);
    public function getBatchProducts($batchId, array $filters = []);
    public function approve($batchId);
    public function reject($batchId);
}

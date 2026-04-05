<?php

namespace Modules\CRM\Contracts\Product;

interface GraphPriceRepositoryInterface
{
    public function paginate(array $filters = []);
    public function getFilters();
    public function find(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
    public function generateAnalytics(array $data);
    public function fetchDailyPrices(array $data);
}

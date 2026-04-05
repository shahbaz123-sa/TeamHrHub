<?php

namespace Modules\CRM\Repositories\Product;

use Exception;
use Illuminate\Support\Collection;
use Modules\CRM\Models\Product\GraphPrice;
use Modules\CRM\Contracts\Product\GraphPriceRepositoryInterface;

class GraphPriceRepository implements GraphPriceRepositoryInterface
{
    protected $model;

    public function __construct(GraphPrice $model)
    {
        $this->model = $model;
    }

    public function paginate(array $filters = [])
    {
        return $this->model
            ->when(isset($filters['q']), function ($query) use ($filters) {
                $q = "%{$filters['q']}%";
                $query->where(function ($qry) use ($q) {
                    $qry->whereAny([
                        'market',
                        'currency',
                        'category_name',
                        'product_name',
                        'brand_name',
                        'unit_name',
                        'currency',
                    ], 'ilike', $q);
                });
            })
            ->when(!empty($filters['category_name']), function ($query) use ($filters) {
                $query->where('category_name', 'ilike', "%{$filters['category_name']}%");
            })
            ->when(!empty($filters['product_name']), function ($query) use ($filters) {
                $query->where('product_name', 'ilike', "%{$filters['product_name']}%");
            })
            ->when(!empty($filters['brand_name']), function ($query) use ($filters) {
                $query->where('brand_name', 'ilike', "%{$filters['brand_name']}%");
            })
            ->when(!empty($filters['unit_name']), function ($query) use ($filters) {
                $query->where('unit_name', 'ilike', "%{$filters['unit_name']}%");
            })
            ->when(!empty($filters['start_date']), function ($query) use ($filters) {
                $query->whereDate('datetime', '>=', $filters['start_date']);
            })
            ->when(!empty($filters['end_date']), function ($query) use ($filters) {
                $query->whereDate('datetime', '<=', $filters['end_date']);
            })
            ->orderBy(data_get($filters, 'sort_by', 'datetime'), data_get($filters, 'order_by', 'desc'))
            ->paginate($filters['per_page'] ?? 10);
    }

    public function getFilters()
    {
        $categories = $this->model->select('category_name')
            ->distinct()
            ->whereNotNull('category_name')
            ->where('category_name', '<>', '')
            ->orderBy('category_name')
            ->pluck('category_name');

        $products = $this->getProductsList();

        $brands = $this->model->select('brand_name')
            ->distinct()
            ->whereNotNull('brand_name')
            ->where('brand_name', '<>', '')
            ->orderBy('brand_name')
            ->pluck('brand_name');

        $units = $this->model->select('unit_name')
            ->distinct()
            ->whereNotNull('unit_name')
            ->where('unit_name', '<>', '')
            ->orderBy('unit_name')
            ->pluck('unit_name');

        return compact('categories', 'products', 'brands', 'units');
    }

    public function getProductsList(array $data = [])
    {
        return $this->model->select('product_name')
            ->distinct()
            ->whereNotNull('product_name')
            ->where('product_name', '<>', '')
            ->when(!empty($data['category_name']), function ($query) use ($data) {
                $query->where('category_name', $data['category_name']);
            })
            ->when(!empty($data['datetime']), function ($query) use ($data) {
                $query->where('datetime', $data['datetime']);
            })
            ->orderBy('product_name')
            ->when(!empty($data['page']) && !empty($data['per_page']), function ($query) use ($data) {
                $query->forPage($data['page'], $data['per_page']);
            })
            ->pluck('product_name');
    }

    public function find(int $id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data)
    {
        $item = $this->find($id);
        $item->update($data);
        return $item;
    }

    public function delete(int $id)
    {
        $item = $this->find($id);
        $item->delete();
    }

    public function generateAnalytics(array $data)
    {
        $categoryName = $data['category'] ?? '';
        $market = $data['market'] ?? 'Pakistan';
        $trendRange = $data['trendRange'] ?? '1Y';

        throw_if(empty($market) || empty($categoryName), new Exception('Invalid market or category.', 422));

        $graphPrices = $this->model
            ->where('category_name', $categoryName)
            ->where('market', $market);

        $hasData = $graphPrices->exists() > 0;

        if (!$hasData) {
            return [
                'status' => true,
                'has_price' => $hasData,
            ];
        }

        $oneYearAgo = date('Y-m-d H:i:s', strtotime('-1 year'));

        $maxPrice = $graphPrices->clone()->where('datetime', '>=', $oneYearAgo)->max('price');
        $minPrice = $graphPrices->clone()->where('datetime', '>=', $oneYearAgo)->min('price');

        $latestDate = $graphPrices->clone()->orderByDesc('datetime')->first()->datetime ?? '';
        $previousDate = $graphPrices->clone()->where('datetime', '<', $latestDate)->orderByDesc('datetime')->first()->datetime ?? '';

        $avgPrice = $graphPrices->clone()->where('datetime', '=', $latestDate)->avg('price');
        $previousAvgPrice = $graphPrices->clone()->where('datetime', '=', $previousDate)->avg('price');

        $currentWeekStart = date('Y-m-d H:i:s', strtotime('monday this week', strtotime($latestDate)));
        $currentWeekEnd = date('Y-m-d H:i:s', strtotime('sunday this week', strtotime($latestDate)));

        $weekMinPrice = $graphPrices->clone()->whereBetween('datetime', [$currentWeekStart, $currentWeekEnd])->min('price');
        $weekMaxPrice = $graphPrices->clone()->whereBetween('datetime', [$currentWeekStart, $currentWeekEnd])->max('price');

        $averagePriceChange = 0;
        if (is_numeric($avgPrice) && is_numeric($previousAvgPrice) && $previousAvgPrice > 0) {
            $averagePriceChange = (($avgPrice - $previousAvgPrice) / $previousAvgPrice) * 100;
            $averagePriceChange = ((int)$averagePriceChange) . "%";
        }

        $ytdStart = date('Y') . '-01-01 00:00:00';
        $priceYtdStart = $graphPrices->clone()->where('datetime', '<=', $ytdStart)->orderByDesc('datetime')->first()->price ?? 0;

        $ytdChange = 0;
        if (is_numeric($avgPrice) && is_numeric($priceYtdStart) && $priceYtdStart > 0) {
            $ytdChange = (($avgPrice - $priceYtdStart) / $priceYtdStart) * 100;
            $ytdChange = ((int)$ytdChange) . "%";
        }

        $priceOneYearAgo = $graphPrices->clone()->where('datetime', '<=', $oneYearAgo)->orderByDesc('datetime')->first()->price ?? 0;

        $oneYearChange = 0;
        if (is_numeric($avgPrice) && is_numeric($priceOneYearAgo) && $priceOneYearAgo > 0) {
            $oneYearChange = (($avgPrice - $priceOneYearAgo) / $priceOneYearAgo) * 100;
            $oneYearChange = ((int)$oneYearChange) . "%";
        }

        switch ($trendRange) {
            case '1M':
                $dateQuery = " datetime >= (CURRENT_DATE - INTERVAL '1 month') ";
                break;
            case '3M':
                $dateQuery = " datetime >= (CURRENT_DATE - INTERVAL '3 month') ";
                break;
            case '6M':
                $dateQuery = " datetime >= (CURRENT_DATE - INTERVAL '6 month') ";
                break;
            case '1Y':
                $dateQuery = " datetime >= (CURRENT_DATE - INTERVAL '1 year') ";
                break;
            case '2Y':
                $dateQuery = " datetime >= (CURRENT_DATE - INTERVAL '2 year') ";
                break;
            case '3Y':
                $dateQuery = " datetime >= (CURRENT_DATE - INTERVAL '3 year') ";
                break;
            case '5Y':
                $dateQuery = " datetime >= (CURRENT_DATE - INTERVAL '5 year') ";
                break;
            case '10Y':
                $dateQuery = " datetime >= (CURRENT_DATE - INTERVAL '10 year') ";
                break;
            case 'A':
            default:
                $dateQuery = ''; // No date filter for 'All'
                break;
        }

        $trendChartData = $this->model->selectRaw("
            DATE(datetime) as trend_date,
            TO_CHAR(AVG(price), 'FM999,999,999,990.00') as avg_price
        ")
            ->where('category_name', $categoryName)
            ->where('market', $market)
            ->whereRaw($dateQuery)
            ->groupByRaw('DATE(datetime)')
            ->orderByRaw('DATE(datetime)')
            ->get();

        $dates = [];
        $prices = [];
        foreach ($trendChartData as $data) {
            $dates[] = $data->trend_date;
            $prices[] = $data->avg_price;
        }

        $pricingUnit = $graphPrices->clone()->orderByDesc('datetime')->first()->unit_name ?? '';

        return [
            'status' => true,
            'has_price' => $hasData,
            'max_price' => number_format($weekMaxPrice, 2),
            'min_price' => number_format($weekMinPrice, 2),
            'unit' => $pricingUnit,
            'max_52_week_price' => number_format($maxPrice, 2),
            'min_52_week_price' => number_format($minPrice, 2),
            'current_price' => number_format($avgPrice, 2),
            'current_date' => $latestDate,
            'last_price_change' => $averagePriceChange,
            'ytd_change' => $ytdChange,
            'one_year_change' => $oneYearChange,
            'trend_data' => [
                'dates' => $dates,
                'prices' => $prices,
            ]
        ];
    }

    public function fetchDailyPrices(array $data)
    {
        $categoryName = $data['category'] ?? 'all';
        throw_if(empty($categoryName), new Exception('Invalid category.', 422));

        $paged = isset($data['paged']) ? max(1, intval($data['paged'])) : 1;
        $perPage = isset($data['per_page']) ? max(1, intval($data['per_page'])) : 10;

        $latestDateTime = $this->model->clone()
            ->when($categoryName !== 'all', function ($query) use ($categoryName) {
                $query->where('category_name', $categoryName);
            })
            ->max('datetime');

        $totalPrices = $this->getProductsList([
            'category_name' => $categoryName !== 'all' ? $categoryName : null,
            'datetime' => $latestDateTime,
        ]);

        $productNames = $this->getProductsList([
            'category_name' => $categoryName !== 'all' ? $categoryName : null,
            'datetime' => $latestDateTime,
            'page' => $paged,
            'per_page' => $perPage,
        ]);

        $priceDetails = [];
        foreach ($productNames as $productName) {
            $priceDetail = $this->model->clone()
                ->select(
                    'product_name',
                    'datetime',
                    'currency',
                    'price',
                    'unit_name',
                    'created_at'
                )
                ->where('product_name', $productName)
                ->when($categoryName !== 'all', function ($query) use ($categoryName) {
                    $query->where('category_name', $categoryName);
                })
                ->where('datetime', $latestDateTime)
                ->orderByDesc('datetime')
                ->first();

            if ($priceDetail) {
                $previousPriceDetail = $this->model->clone()
                    ->select('price', 'datetime')
                    ->where('product_name', $productName)
                    ->when($categoryName !== 'all', function ($query) use ($categoryName) {
                        $query->where('category_name', $categoryName);
                    })
                    ->where('datetime', '<', $priceDetail->datetime)
                    ->orderByDesc('datetime')
                    ->first();

                $previousPrice = $previousPriceDetail->price ?? 0;

                $change = $priceDetail->price - $previousPrice;
                $changePercentage = $previousPrice > 0 ? (($priceDetail->price - $previousPrice) / $previousPrice) * 100 : 0;
                $changePercentage = number_format($changePercentage, 2, '.', '');

                $latestDate = date('d M Y', strtotime($priceDetail->datetime));
                $previousDate = $previousPriceDetail && $previousPriceDetail->datetime ? date('d M Y', strtotime($previousPriceDetail->datetime)) : '';

                $priceDetails[] = [
                    'product_name' => $priceDetail->product_name,
                    'currency' => $priceDetail->currency,
                    'uploaded_at' => $priceDetail->created_at->format('Y-m-d H:i:s'),
                    'latest_date' => $latestDate,
                    'previous_date' => $previousDate,
                    'price' => $priceDetail->price,
                    'previous_price' => $previousPrice,
                    'change' => $change,
                    'change_percentage' => $changePercentage,
                    'unit_name' => $priceDetail->unit_name,
                ];
            }
        }

        throw_if(empty($priceDetails), new Exception('No prices found for this category.', 200));

        $totalPages = ceil($totalPrices->count() / $perPage);

        return [
            'prices' => $priceDetails,
            'category' => ucfirst($categoryName),
            'total_pages' => $totalPages,
            'paged' => $paged,
        ];
    }
}

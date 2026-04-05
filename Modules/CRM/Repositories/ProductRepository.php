<?php

namespace Modules\CRM\Repositories;

use Modules\CRM\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\CRM\Traits\File\FileManager;
use Modules\CRM\Contracts\ProductRepositoryInterface;
use Modules\CRM\Models\Product\Pivot\AttributeValueProduct;

class ProductRepository implements ProductRepositoryInterface
{
  use FileManager;

  protected $model;

  public function __construct(Product $model)
  {
    $this->model = $model;
  }

  public function paginate(array $filters = [])
  {
    return $this->model
      ->with(['uom', 'brand'])
      ->when(isset($filters['q']), function ($query) use ($filters) {
        $query->whereAny(['name', 'sku', 'wc_slug'], 'ilike', "%{$filters['q']}%");
      })
      ->when(isset($filters['status']), function ($query) use ($filters) {
        $query->where('status', $filters['status']);
      })
      ->when(isset($filters['stock']), function ($query) use ($filters) {
        $query->where('stock_status', $filters['stock']);
      })
      ->when(isset($filters['type']), function ($query) use ($filters) {
        $query->where('type', $filters['type']);
      })
      ->when(isset($filters['uom']), function ($query) use ($filters) {
        $query->where('uom_id', $filters['uom']);
      })
      ->when(isset($filters['brand']), function ($query) use ($filters) {
        $query->where('brand_id', $filters['brand']);
      })
      ->when(isset($filters['category']), function ($query) use ($filters) {
        $query->whereHas('categories', fn($relationshipQuery) => $relationshipQuery->where('category_id', '=', $filters['category']));
      })
      ->when(isset($filters['sub_category']), function ($query) use ($filters) {
        $query->whereHas('categories', fn($relationshipQuery) => $relationshipQuery->where('category_id', '=', $filters['sub_category']));
      })
      ->whereIn('type', ['simple', 'variable'])
      ->orderBy(
        data_get($filters, 'sort_by', 'id'),
        data_get($filters, 'order_by', 'desc')
      )
      ->paginate($filters['per_page'] ?? 10);
  }

  public function getParents(array $data)
  {
    return $this->model
      ->activeOnly()
      ->where('type', 'variable')
      ->paginate($data['per_page'] ?? 10);
  }

  public function find(int $id)
  {
    return $this->model
      ->with([
        'uom',
        'brand',
        'images',
        'tags',
        'categories',
        'prices',
        'variations.attributeValues.attribute',
        'variations.prices',
      ])
      ->findOrFail($id);
  }

  public function create(array $data)
  {
    $product = $this->model->create($data);

    if (isset($data['categories'])) {
      $product->categories()->sync($data['categories']);
    }

    if (isset($data['tags'])) {
      $product->tags()->sync($data['tags']);
    }

    if (isset($data['images'])) {
      $this->attachImages($product, $data['sku'], $data['images']);
    }

    if (isset($data['city_wise_prices'])) {
      $this->attachPrices($product, $data['city_wise_prices']);
    }

    if ($data['has_variation']) {
      $this->attachVariations(
        $product,
        $data['variations'],
        collect($data)->except([
          'categories',
          'tags',
          'images',
          'variations',
        ])
      );
    }

    return $product ?? null;
  }

  public function update(int $id, array $data)
  {
    $product = $this->find($id);
    $product->update($data);

    if (isset($data['categories'])) {
      $product->categories()->sync($data['categories']);
    }

    if (isset($data['tags'])) {
      $product->tags()->sync($data['tags']);
    }

    if (isset($data['existing_images'])) {
      $product->images()->whereNotIn('id', $data['existing_images'])->get()->each(function ($image) {
        $this->deleteFile($image->src);
        $image->delete();
      });
    } else {
      $product->images->each(function ($image) {
        $this->deleteFile($image->src);
        $image->delete();
      });
    }

    if (isset($data['images'])) {
      $this->attachImages($product, $data['sku'], $data['images']);
    }

    $product->prices()->delete();
    if (!isset($data['city_wise_prices'])) $product->variations()->delete();

    if (isset($data['city_wise_prices'])) {
      $this->attachPrices($product, $data['city_wise_prices']);
    }

    if ($data['has_variation']) {
      $this->attachVariations(
        $product,
        $data['variations'],
        collect($data)->except([
          'categories',
          'tags',
          'images',
          'variations',
        ])
      );
    }

    return $product;
  }

  public function attachPrices(&$product, $cityWisePrices)
  {
    foreach ($cityWisePrices as $cityId => $cityPrice) {
      if ($cityId > 0 && $cityPrice > 0) {
        $product->prices()->create([
          'city_id' => $cityId,
          'price' => $cityPrice,
        ]);
      }
    }
  }

  public function attachImages(&$product, $sku, $images)
  {
    foreach ($images as $image) {
      if ($image instanceof \Illuminate\Http\UploadedFile) {
        $fileName = Str::replace('%', '', $image->getClientOriginalName());
        $imageSrc = $this->uploadFile(
          $image,
          "product/images/{$sku}",
          $fileName
        );

        $product->images()->updateOrCreate([
          'src' => $imageSrc
        ]);
      }
    }
  }

  public function attachVariations(&$product, $variations, $inheritAttributes)
  {
    foreach ($variations as $variationData) {
      DB::beginTransaction();
      try {
        $variation = $this->model->create(
          $inheritAttributes->merge($variationData)->merge([
            'type' => 'variation',
            'parent_id' => $product->id,
            'stock_status' => 'instock',
          ])->toArray()
        );

        if (isset($variationData['city_wise_prices'])) {
          $this->attachPrices($variation, $variationData['city_wise_prices']);
        }

        collect($variationData['options'])
          ->filter(fn($option) => !empty($option['attribute']) && !empty($option['value']))
          ->each(function ($option) use ($variation) {
            AttributeValueProduct::updateOrCreate([
              'product_id' => $variation->id ?? 0,
              'attribute_id' => $option['attribute']['id'],
              'attribute_value_id' => $option['value']['id'],
            ]);
          });
      } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
      }
      DB::commit();
    }
  }

  public function delete(int $id)
  {
    $product = $this->find($id);

    if ($product->images) {
      $product->images()->delete();
    }

    $product->delete();
  }
}

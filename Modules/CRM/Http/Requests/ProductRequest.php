<?php

namespace Modules\CRM\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Rules\SlugRegexRule;
use Modules\CRM\Http\Requests\BaseFormRequest;
use Modules\CRM\Models\Product;

class ProductRequest extends BaseFormRequest
{
    public function authorize()
    {
        $user = $this->user();
        if ($this->isMethod('POST')) {
            return $user && $user->can('product.create');
        }
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return $user && $user->can('product.update');
        }
        return $user && $user->can('product.read');
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'variations' => json_decode($this->variations, true) ?? [],
            'sku' => $this->sku,
            'type' => 'simple',
        ]);

        if (isset($this->has_variation) && is_string($this->has_variation)) {
            $this->merge([
                'has_variation' => filter_var($this->has_variation, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if ($this->has_variation > 0) {
            $this->merge([
                'type' => 'variable',
            ]);
        }

        if (isset($this->ask_for_quote) && is_string($this->ask_for_quote)) {
            $this->merge([
                'ask_for_quote' => filter_var($this->ask_for_quote, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if (isset($this->stock_status) && is_string($this->stock_status)) {
            $this->merge([
                'stock_status' => in_array($this->stock_status, ["true", "instock"]) ? 'instock' : 'outofstock',
            ]);
        }

        if (isset($this->categories) && is_string($this->categories)) {
            $this->merge([
                'categories' => explode(',', $this->categories),
            ]);
        }

        if (isset($this->tags) && is_string($this->tags)) {
            $this->merge([
                'tags' => explode(',', $this->tags),
            ]);
        }
    }

    public function rules()
    {
        return [
            'sku' => [
                'required',
                'string',
                new SlugRegexRule(),
                Rule::unique('crm.products', 'sku')->ignore($this->route('product', 0)),
            ],
            'wc_slug' => [
                'required',
                'string',
                new SlugRegexRule(),
                Rule::unique('crm.products', 'wc_slug')
                    ->ignore($this->route('product', 0))
                    ->where(function ($query) {
                        $query->whereNull('parent_id')
                            ->orWhere('parent_id', "<=", 0);
                    }),
            ],
            'name' => 'required|string|max:255',
            'type' => 'required|in:simple,variable',
            'status' => 'required|in:publish,inactive',
            'stock_status' => 'required|in:instock,outofstock',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|numeric|min:1',
            'min_quantity' => 'required|numeric|min:1',
            'max_quantity' => 'required|numeric|min:1',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'uom_id' => 'nullable|exists:crm.product_uoms,id',
            'categories' => 'nullable|array',
            'tags' => 'nullable|array',
            'images' => 'nullable|array',
            'existing_images' => 'nullable|array',
            'brand_id' => 'nullable|exists:crm.product_brands,id',
            'ask_for_quote' => 'required|boolean',
            'has_variation' => 'required|boolean',
            'city_wise_prices' => 'required_if:has_variation,1|array',
            'variations' => 'required_if:has_variation,0|sometimes|array',
            'variations.*.sku' => [
                'required',
                'string',
                new SlugRegexRule(),
            ],
            'variations.*.name' => 'required|string|max:255',
            'variations.*.price' => 'required|numeric|min:0',
            'variations.*.quantity' => 'required|numeric|min:1',
            'variations.*.min_quantity' => 'required|numeric|min:1',
            'variations.*.max_quantity' => 'required|numeric|min:1',
            'variations.*.city_wise_prices' => 'required|array',
            'variations.*.options' => 'required|array',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $variations = $this->input('variations') ?? [];

            foreach ($variations as $index => $variation) {
                $sku = $variation['sku'] ?? null;
                $id = $variation['id'] ?? null;

                if ($sku) {
                    $query = Product::where('sku', $sku);

                    if ($id) {
                        $query->where('id', '<>', $id);
                    }

                    if ($query->exists()) {
                        $validator->errors()->add("variations.$index.sku", "The SKU '{$sku}' has already been taken.");
                    }
                }
            }
        });
    }
}

<?php

namespace Modules\CRM\Http\Requests\Product;

use Illuminate\Support\Facades\Auth;
use Modules\CRM\Http\Requests\BaseFormRequest;

class GraphPriceRequest extends BaseFormRequest
{
    public function authorize()
    {
        $user = $this->user();
        if ($this->isMethod('POST')) {
            return $user && $user->can('product_graph_price.create');
        }
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return $user && $user->can('product_graph_price.update');
        }
        return $user && $user->can('product_graph_price.read');
    }

    public function rules()
    {
        return [
            'category_name' => 'nullable|string|max:255',
            'product_name' => 'required|string|max:255',
            'brand_name' => 'nullable|string|max:255',
            'datetime' => 'required|date',
            'datetime_raw' => 'nullable|string',
            'price' => 'nullable',
            'price_raw' => 'nullable|string',
            'market' => 'required|string|max:255',
            'currency' => 'required|string|max:10',
            'unit_name' => 'required|string|max:255',
            'uploaded_by' => 'required|integer|exists:users,id',
        ];
    }
}

<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class StoreTaxSlabRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Permissions enforced at route/controller/middleware level in this project
        return true;
    }

    public function rules(): array
    {
         return [
             'name' => 'required|string|max:255|unique:tax_slabs,name',
             'min_salary' => 'required|numeric|min:0',
             'max_salary' => [
                 'required',
                 'numeric',
                 'gt:min_salary',
             ],
             'tax_rate' => 'required|numeric|min:0|max:100',
             'fixed_amount' => 'required|numeric|min:0',
             'exceeding_threshold' => [
                 'required',
                 'numeric',
                 'min:0',
                 function ($attribute, $value, $fail) {
                     $min = $this->input('min_salary');
                     $max = $this->input('max_salary');
                     if ($value < ($min - 1) ) {
                         $fail('The exceeding threshold must be greater than or equal to the minimum salary.');
                     }
                     if ($max !== null && $value > $max) {
                         $fail('The exceeding threshold must be less than or equal to the maximum salary.');
                     }
                 },
             ],
             // Custom rule to prevent overlapping slabs (adjacent allowed)
             'no_overlap' => [function (
                 $attribute, $value, $fail
             ) {
                 $min = $this->input('min_salary');
                 $max = $this->input('max_salary');
                 $exists = DB::table('tax_slabs')
                     ->where(function ($query) use ($min, $max) {
                         $query->where('min_salary', '<=', $max)
                               ->where('max_salary', '>=', $min);
                     })
                     ->exists();
                 if ($exists) {
                     $fail('The provided salary range overlaps with an existing tax slab. Adjacent ranges are allowed, but not intersecting.');
                 }
             }],
         ];
    }
}

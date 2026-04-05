<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use function Laravel\Prompts\error;

class UpdateTaxSlabRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Route model binding uses {taxSlab} in Modules/HRM/Routes/api.php
        $bound = $this->route('taxSlab');
        $id = $bound?->id
            ?? $this->route('tax_slab')?->id
            ?? $this->route('tax_slab')
            ?? $this->route('id');

        // Ensure we never generate "id <>" with empty value in SQL
        $id = is_numeric($id) ? (int) $id : null;

        // Custom rule to prevent overlapping slabs (adjacent allowed)
        $noOverlapRule = function ($attribute, $value, $fail) use ($id) {
            $min = $this->input('min_salary');
            $max = $this->input('max_salary');
            $exists = DB::table('tax_slabs')
                ->where('id', '!=', $id)
                ->where(function ($query) use ($min, $max) {
                    $query->whereBetween('max_salary', [$min, $max])
                          ->orWhereBetween('min_salary', [$min, $max]);
                })
                ->exists();
            if ($exists) {
                $fail('The provided salary range overlaps with an existing tax slab. Adjacent ranges are allowed, but not intersecting.');
            }
        };

         return [
             'name' => 'required|string|max:255|unique:tax_slabs,name,' . ($id ?? 'NULL'),
             'min_salary' => 'required|numeric|min:0',
             'max_salary' => 'nullable|numeric|gt:min_salary',
             'tax_rate' => 'required|numeric|min:0|max:100',
             'fixed_amount' => 'required|numeric|min:0',
             'exceeding_threshold' => [
                 'required',
                 'numeric',
                 'min:0',
                 function ($attribute, $value, $fail) {
                     $min = $this->input('min_salary');
                     $max = $this->input('max_salary');
                     if ($value < ($min - 1)) {
                         $fail('The exceeding threshold must be greater than or equal to the minimum salary.');
                     }
                     if ($max !== null && $value > $max) {
                         $fail('The exceeding threshold must be less than or equal to the maximum salary.');
                     }
                 },
             ],
             'no_overlap' => [$noOverlapRule],
         ];
    }
}

<?php

namespace Modules\HRM\Http\Requests\Leave\Hr;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ApproveRejectRequest extends FormRequest
{
    public function authorize()
    {
        $user = $this->user();
        return $user && $user->can('leave_hr_approval.create');
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)
        );
    }

    public function rules()
    {
        $rules = [
            'hr_status' => 'required|in:"reject","approve"'
        ];

        if ($this->hr_status === 'approve') {
            $rules = [
                'is_paid' => 'required|in:"true","false"',
                'applied_start_date' => 'required|date',
                'applied_end_date' => 'required|date|after_or_equal:applied_start_date',
                'total_paid_days' => 'required_if:is_paid,false|integer|min:0',
                'total_unpaid_days' => 'required_if:is_paid,false|integer|min:0',
                'paid_start_date' => 'required_if:is_paid,false|date',
                'paid_end_date' => 'required_if:is_paid,false|date|after_or_equal:paid_start_date',
                'unpaid_start_date' => 'required_if:is_paid,false|date',
                'unpaid_end_date' => 'required_if:is_paid,false|date|after_or_equal:unpaid_start_date',
            ];
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->hr_status === 'approve' && $this->is_paid === 'false') {
                // Check that dates are within applied leave range
                foreach (
                    [
                        'paid_start_date',
                        'paid_end_date',
                        'unpaid_start_date',
                        'unpaid_end_date',
                    ] as $field
                ) {
                    $value = $this->$field;
                    if ($value < $this->applied_start_date || $value > $this->applied_end_date) {
                        $validator->errors()->add($field, ucfirst(str_replace('_', ' ', $field)) . ' must be within the applied leave period.');
                    }
                }

                // Check for overlap between paid and unpaid
                if (
                    $this->paid_start_date <= $this->unpaid_end_date &&
                    $this->paid_end_date >= $this->unpaid_start_date
                ) {
                    $validator->errors()->add('paid_start_date', 'Paid and Unpaid leave dates must not overlap.');
                    $validator->errors()->add('unpaid_start_date', 'Paid and Unpaid leave dates must not overlap.');
                }

                // Calculate actual paid days (inclusive)
                if ($this->paid_start_date && $this->paid_end_date) {
                    $paidStart = Carbon::parse($this->paid_start_date);
                    $paidEnd = Carbon::parse($this->paid_end_date);
                    $actualPaidDays = $paidStart->diffInDays($paidEnd) + 1;

                    if ((int) $this->total_paid_days !== (int) $actualPaidDays) {
                        $validator->errors()->add(
                            'total_paid_days',
                            "Total paid days ({$this->total_paid_days}) must match the number of days between paid start and end date ({$actualPaidDays})."
                        );
                    }
                }

                // Calculate actual unpaid days (inclusive)
                if ($this->unpaid_start_date && $this->unpaid_end_date) {
                    $unpaidStart = Carbon::parse($this->unpaid_start_date);
                    $unpaidEnd = Carbon::parse($this->unpaid_end_date);
                    $actualUnpaidDays = $unpaidStart->diffInDays($unpaidEnd) + 1;

                    if ((int) $this->total_unpaid_days !== (int) $actualUnpaidDays) {
                        $validator->errors()->add(
                            'total_unpaid_days',
                            "Total unpaid days ({$this->total_unpaid_days}) must match the number of days between unpaid start and end date ({$actualUnpaidDays})."
                        );
                    }
                }
            }
        });
    }
}

<?php

namespace Modules\HRM\Http\Requests;

use Modules\HRM\Rules\FileOrPath;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class TicketRequest extends FormRequest
{
    public function authorize()
    {
        $user = $this->user();
        if ($this->isMethod('POST')) {
            return $user && $user->can('ticket.create');
        }
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return $user && $user->can('ticket.update');
        }
        return $user && $user->can('ticket.read');
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
        return [
            'employee_id'   => 'required|exists:employees,id',
            'department_id' => 'required|exists:departments,id',
            'poc_id'        => 'required|exists:employees,id',
            'category_id'   => 'required|exists:ticket_categories,id',
            'description'   => 'required|string',
            'attachment'    => ['nullable', 'sometimes', new FileOrPath(['pdf']), 'max:2048'],
            'status'        => 'required|in:Open,Pending,Resolved,Closed',
        ];
    }
}

<?php

namespace App\Http\Requests\Customer\Order;

use App\Enums\CancellationReason;
use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;

class CancelOrderRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'integer', Rule::in(CancellationReason::customerCancellationCases())],
            'note'   => ['nullable', 'string', 'max:500'],
        ];
    }
}
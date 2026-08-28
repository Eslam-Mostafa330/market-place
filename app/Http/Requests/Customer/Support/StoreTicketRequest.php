<?php

namespace App\Http\Requests\Customer\Support;

use App\Enums\TicketCategory;
use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subject'  => ['required', 'string', 'min:3', 'max:150'],
            'message'  => ['required', 'string', 'min:2', 'max:'. config('support.message_max_length')],
            'category' => ['required', 'integer', Rule::in(TicketCategory::values())],
            'order_id' => [Rule::requiredIf(fn () => (int) $this->input('category') === TicketCategory::ORDER->value), 'nullable', 'uuid'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'order_id.required' => __('support.order_required_for_category'),
        ];
    }
}

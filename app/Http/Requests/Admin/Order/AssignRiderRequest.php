<?php

namespace App\Http\Requests\Admin\Order;

use App\Http\Requests\FormRequest;

class AssignRiderRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rider_id' => ['required', 'uuid'],
        ];
    }
}
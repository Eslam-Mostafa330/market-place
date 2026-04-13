<?php

namespace App\Http\Requests\Customer\LoyaltyPoints;

use App\Http\Requests\FormRequest;

class RedeemPointsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'points' => ['required', 'integer', 'min:1'],
        ];
    }
}
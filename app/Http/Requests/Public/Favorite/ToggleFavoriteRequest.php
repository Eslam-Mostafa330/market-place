<?php

namespace App\Http\Requests\Public\Favorite;

use App\Enums\DefineStatus;
use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;

class ToggleFavoriteRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'uuid', Rule::exists('products', 'id')->where(function ($query) {
                    $query->where('status', DefineStatus::ACTIVE);
                }),
            ],
        ];
    }
}
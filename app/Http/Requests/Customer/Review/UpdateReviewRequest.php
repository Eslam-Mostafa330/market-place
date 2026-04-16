<?php

namespace App\Http\Requests\Customer\Review;

use App\Http\Requests\FormRequest;

class UpdateReviewRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rate'        => ['sometimes', 'required', 'integer', 'min:1', 'max:5'],
            'full_review' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
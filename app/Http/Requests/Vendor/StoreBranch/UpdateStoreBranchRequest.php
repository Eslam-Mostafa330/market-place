<?php

namespace App\Http\Requests\Vendor\StoreBranch;

use App\Enums\DefineStatus;
use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStoreBranchRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'              => ['sometimes', 'required', 'string', 'max:255', Rule::unique('store_branches', 'name')->where('store_id', $this->store->id)->ignore($this->branch->id)],
            'address'           => ['sometimes', 'required', 'string', 'max:500'],
            'city'              => ['sometimes', 'required', 'string', 'max:255'],
            'area'              => ['nullable', 'string', 'max:255'],
            'phone'             => ['nullable', 'string', 'regex:/^[0-9\s\-\+\(\)]+$/', 'max:25'],
            'delivery_fee'      => ['sometimes', 'required', 'numeric', 'min:0'],
            'delivery_time_max' => ['sometimes', 'required', 'integer', 'min:1', 'max:65535'],
            'latitude'          => ['sometimes', 'required', 'numeric', 'between:-90,90'],
            'longitude'         => ['sometimes', 'required', 'numeric', 'between:-180,180'],
            'status'            => ['sometimes', 'required', 'integer', Rule::in(DefineStatus::values())],
        ];
    }
}
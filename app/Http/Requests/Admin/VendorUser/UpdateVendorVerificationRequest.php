<?php

namespace App\Http\Requests\Admin\VendorUser;

use App\Enums\VendorVerificationStatus;
use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVendorVerificationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'verification_status' => ['required', 'integer', Rule::in(VendorVerificationStatus::allowedAdminActions())],
            'rejection_reason'    => ['required_if:verification_status,' . VendorVerificationStatus::REJECTED->value, 'prohibited_if:verification_status,' . VendorVerificationStatus::VERIFIED->value, 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'verification_status.in'         => __('validation.custom.verification_status_invalid'),
            'rejection_reason.required_if'   => __('validation.custom.rejection_reason_required'),
            'rejection_reason.prohibited_if' => __('validation.custom.rejection_reason_prohibited'),
        ];
    }
}
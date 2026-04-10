<?php

namespace App\Http\Requests\Admin\Payout;

use App\Enums\PayoutMethod;
use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;

class MarkCompletePayoutRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'payout_method' => ['required', 'integer', Rule::in(PayoutMethod::values())],
            'reference'     => ['nullable', 'string', 'max:255', Rule::requiredIf(fn () => $this->payout_method == PayoutMethod::BANK_TRANSFER->value)],
            'payout_proof'  => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048', Rule::requiredIf(fn () => $this->payout_method == PayoutMethod::PHONE_WALLET->value)],
            'notes'         => ['nullable', 'string', 'max:500', Rule::requiredIf(fn () => in_array($this->integer('payout_method'), PayoutMethod::requiresForNotes()))],
        ];
    }

    public function messages(): array
    {
        return [
            'reference.required'    => __('payment.payout.reference_required'),
            'payout_proof.required' => __('payment.payout.payout_proof_required'),
            'notes.required'        => __('payment.payout.notes_required'),
        ];
    }
}
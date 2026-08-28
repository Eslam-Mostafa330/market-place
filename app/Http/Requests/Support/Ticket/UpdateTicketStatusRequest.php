<?php

namespace App\Http\Requests\Support\Ticket;

use App\Enums\TicketStatus;
use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketStatusRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'integer', Rule::in(TicketStatus::agentAssignable())],
        ];
    }
}

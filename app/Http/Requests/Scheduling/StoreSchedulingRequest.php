<?php

namespace App\Http\Requests\Scheduling;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSchedulingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isClient = $this->user()?->userType?->name === 'cliente';

        return [
            'client_id'  => $isClient ? 'nullable|integer' : 'required|integer|exists:clients,id',
            'start_date' => 'required|date',
        ];
    }
}

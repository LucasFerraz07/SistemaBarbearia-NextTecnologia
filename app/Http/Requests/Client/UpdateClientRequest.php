<?php

namespace App\Http\Requests\Client;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateClientRequest extends FormRequest
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
        return [
            'name'   => 'sometimes|string|max:255',
            'email'  => 'sometimes|string|email|max:255|unique:users,email,' . $this->route('client'),
            'phone'  => 'sometimes|string|max:20',
            'cep'    => 'sometimes|string|max:9',
            'number' => 'sometimes|string|max:20',
        ];
    }
}

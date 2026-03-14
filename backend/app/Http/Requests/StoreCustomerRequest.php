<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\ValidCpf;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:customers,email'],
            'cpf' => ['required', 'string', 'size:11', 'unique:customers,cpf', new ValidCpf()],
            'phone' => ['required', 'string', 'regex:/^\d{10,11}$/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $dataToMerge = [];

        if ($this->has('cpf')) {
            $dataToMerge['cpf'] = preg_replace('/\D/', '', $this->cpf);
        }

        if ($this->has('phone')) {
            $dataToMerge['phone'] = preg_replace('/\D/', '', $this->phone);
        }

        if (! empty($dataToMerge)) {
            $this->merge($dataToMerge);
        }
    }
}

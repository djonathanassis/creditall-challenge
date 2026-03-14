<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\ValidCpf;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
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
        $customer = $this->route('customer');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($customer)],
            'cpf' => ['sometimes', 'string', 'size:11', Rule::unique('customers', 'cpf')->ignore($customer), new ValidCpf()],
            'phone' => ['sometimes', 'string', 'regex:/^\d{10,11}$/'],
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

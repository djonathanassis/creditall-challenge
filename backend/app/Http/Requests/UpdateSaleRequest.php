<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\SaleStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSaleRequest extends FormRequest
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
            'status' => ['sometimes', Rule::enum(SaleStatus::class)],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->has('status') && ! $validator->errors()->count()) {
                $sale = $this->route('sale');
                $newStatus = SaleStatus::from($this->status);

                if (! $sale->status->canTransitionTo($newStatus)) {
                    $validator->errors()->add(
                        'status',
                        __('validation.custom.invalid_status_transition', [
                            'from' => $sale->status->value,
                            'to' => $newStatus->value,
                        ])
                    );
                }
            }
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSaleRequest extends FormRequest
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
            'customer_id' => ['required', 'exists:customers,id'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $validator->errors()->count()) {
                $this->validateInventory($validator);
            }
        });
    }

    private function validateInventory($validator): void
    {
        foreach ($this->items as $index => $item) {
            $product = Product::find($item['product_id']);

            if ($product && $product->stock_quantity < $item['quantity']) {
                $validator->errors()->add(
                    "items.{$index}.quantity",
                    __('validation.custom.insufficient_stock', [
                        'available' => $product->stock_quantity,
                        'product' => $product->name,
                    ])
                );
            }
        }
    }
}

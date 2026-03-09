<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer' => $this->whenLoaded('customer', function () {
                return [
                    'id' => $this->customer->id,
                    'name' => $this->customer->name,
                    'email' => $this->customer->email,
                ];
            }),
            'status' => $this->status->value,
            'status_label' => ucfirst($this->status->value),
            'subtotal_amount' => number_format((float) $this->subtotal, 2, '.', ''),
            'discount_percentage' => $this->discount_percentage ? number_format((float) $this->discount_percentage, 2, '.', '') : null,
            'discount_amount' => number_format((float) $this->discount_amount, 2, '.', ''),
            'total_amount' => number_format((float) $this->total_amount, 2, '.', ''),
            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product' => [
                            'id' => $item->product->id,
                            'name' => $item->product->name,
                            'price' => number_format((float) $item->unit_price, 2, '.', ''),
                        ],
                        'quantity' => $item->quantity,
                        'unit_price' => number_format((float) $item->unit_price, 2, '.', ''),
                        'subtotal' => number_format((float) $item->subtotal, 2, '.', ''),
                    ];
                });
            }),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'is_deleted' => $this->trashed(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }
}

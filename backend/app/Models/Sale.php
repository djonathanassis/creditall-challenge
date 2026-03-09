<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SaleStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $customer_id
 * @property string $subtotal
 * @property string $discount_percentage
 * @property string $discount_amount
 * @property string $total_amount
 * @property SaleStatus $status
 * @property string|null $subtotal_amount
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read int $total_items
 * @property-read float $final_amount
 * @property-read Customer $customer
 * @property-read Collection<int, SaleItem> $items
 */
class Sale extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var string[]
     */
    protected $fillable = [
        'customer_id',
        'subtotal',
        'discount_percentage',
        'discount_amount',
        'total_amount',
        'status',
        'subtotal_amount',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_percentage' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'status' => SaleStatus::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function getTotalItemsAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    public function getFinalAmountAttribute(): float
    {
        return $this->subtotal - $this->discount_amount;
    }

    public function scopeByStatus($query, SaleStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeRecent($query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeByDateRange($query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function calculateTotals(): void
    {
        $this->subtotal = $this->items->sum('subtotal');
        $this->discount_amount = ($this->subtotal * $this->discount_percentage) / 100;
        $this->total_amount = $this->subtotal - $this->discount_amount;
    }

    public function canChangeStatusTo(SaleStatus $newStatus): bool
    {
        return $this->status->canTransitionTo($newStatus);
    }

    public function updateStatus(SaleStatus $newStatus): bool
    {
        if (! $this->canChangeStatusTo($newStatus)) {
            return false;
        }

        $oldStatus = $this->status;
        $this->status = $newStatus;
        $this->save();

        $this->handleInventoryChanges($oldStatus, $newStatus);

        return true;
    }

    protected function handleInventoryChanges(SaleStatus $oldStatus, SaleStatus $newStatus): void
    {
        foreach ($this->items as $item) {
            $product = $item->product;

            if ($oldStatus === SaleStatus::PENDING && $newStatus === SaleStatus::COMPLETED) {
                $product->decreaseStock($item->quantity);
            }

            if ($oldStatus === SaleStatus::COMPLETED && $newStatus === SaleStatus::CANCELLED) {
                $product->increaseStock($item->quantity);
            }

            if (
                $oldStatus === SaleStatus::CANCELLED
                && $newStatus === SaleStatus::PENDING
                && $product->stock_quantity < $item->quantity
            ) {
                throw new \RuntimeException("Insufficient stock for product: {$product->name}");
            }
        }
    }

    protected static function boot(): void
    {
        parent::boot();

        static::deleting(static function ($sale) {
            if ($sale->isForceDeleting()) {
                $sale->items()->forceDelete();
            } else {
                $sale->items()->delete();
            }
        });

        static::restoring(static function ($sale) {
            $sale->items()->onlyTrashed()->restore();
        });
    }
}

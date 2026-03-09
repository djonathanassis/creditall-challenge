<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $cpf
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read float $total_purchases
 * @property-read int $total_orders
 * @property-read string $formatted_cpf
 * @property-read string $masked_cpf
 * @property-read Collection<int, Sale> $sales
 */
class Customer extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
        'email',
        'cpf',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function getTotalPurchasesAttribute(): float
    {
        return (float) $this->sales()->where('status', 'completed')->sum('total_amount');
    }

    public function getTotalOrdersAttribute(): int
    {
        return $this->sales()->count();
    }

    public function getFormattedCpfAttribute(): string
    {
        $cpf = preg_replace('/\D/', '', $this->cpf);

        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
    }

    public function getMaskedCpfAttribute(): string
    {
        $cpf = $this->formatted_cpf;

        return substr($cpf, 0, 3) . '.***.**' . substr($cpf, -2);
    }

    public function scopeSearch($query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        });
    }
}

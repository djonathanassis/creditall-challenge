<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\HasAssociationsInterface;
use App\Contracts\HasStockInterface;
use App\Contracts\ProductRepositoryInterface;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ProductRepository extends AbstractRepository implements HasAssociationsInterface, HasStockInterface, ProductRepositoryInterface
{
    /**
     * @var class-string<Product>
     */
    protected string $model = Product::class;

    /**
     * @var array<string>
     */
    protected array $searchableFields = ['name', 'description'];

    /**
     * @var array<string>
     */
    protected array $filterableFields = [
        'in_stock',
        'price_min',
        'price_max',
    ];

    /**
     * @var array<string>
     */
    protected array $sortableFields = ['name', 'price', 'stock_quantity', 'created_at', 'updated_at'];

    /**
     * {@inheritdoc}
     */
    public function hasAssociatedSales(Model $model): bool
    {
        /** @var Product $model */
        return $model->saleItems()->exists();
    }

    /**
     * {@inheritdoc}
     */
    public function canBeDeleted(Model $model): bool
    {
        return ! $this->hasAssociatedSales($model);
    }

    /**
     * {@inheritdoc}
     */
    public function getAssociatedSalesCount(Model $model): int
    {
        /** @var Product $model */
        return $model->saleItems()->count();
    }

    /**
     * {@inheritdoc}
     */
    public function getModelsWithoutAssociations(): Collection
    {
        return Product::whereDoesntHave('saleItems')->get();
    }

    /**
     * {@inheritdoc}
     */
    public function decrementStock(Product $product, int $quantity): void
    {
        $product->decreaseStock($quantity);
    }

    /**
     * {@inheritdoc}
     */
    public function incrementStock(Product $product, int $quantity): void
    {
        $product->increaseStock($quantity);
    }

    /**
     * {@inheritdoc}
     */
    public function hasSufficientStock(Product $product, int $requiredQuantity): bool
    {
        return $product->stock_quantity >= $requiredQuantity;
    }

    /**
     * {@inheritdoc}
     */
    public function getLowStockProducts(int $threshold = 10): Collection
    {
        return $this->model::query()->where('stock_quantity', '<=', $threshold)->get();
    }

    /**
     * {@inheritdoc}
     */
    protected function customizeListingQuery(Builder $query, Request $request): Builder
    {
        if ($request->input('in_stock')) {
            $query->inStock();
        }

        return $query;
    }
}

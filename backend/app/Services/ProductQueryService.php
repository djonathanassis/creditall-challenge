<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ProductRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

readonly class ProductQueryService
{
    public function __construct(
        private ProductRepositoryInterface $productRepository
    ) {}

    public function getPaginatedProducts(Request $request): LengthAwarePaginator
    {
        return $this->productRepository->buildListingQuery($request)
            ->paginate($request->input('per_page', 15));
    }

    public function searchProducts(string $search, int $limit = 10): Collection
    {
        return $this->productRepository->buildListingQuery(
            new Request(['search' => $search])
        )->limit($limit)->get();
    }
}

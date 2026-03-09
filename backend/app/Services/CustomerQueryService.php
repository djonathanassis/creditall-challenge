<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\CustomerRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

readonly class CustomerQueryService
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository
    ) {}

    public function getPaginatedCustomers(Request $request): LengthAwarePaginator
    {
        return $this->customerRepository->buildListingQuery($request)
            ->paginate($request->input('per_page', 15));
    }

    /**
     * @return Collection
     */
    public function searchCustomers(string $search, int $limit = 10)
    {
        return $this->customerRepository->buildListingQuery(
            new Request(['search' => $search])
        )->limit($limit)->get();
    }
}

<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

interface CustomerRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @param Customer $model
     * @return bool
     */
    public function hasAssociatedSales(Customer $model): bool;

    /**
     * @param int $id
     * @return Customer|null
     */
    public function findWithSalesStats(int $id): ?Customer;

    /**
     * @param Customer $customer
     * @param Request $request
     * @return LengthAwarePaginator
     */
    public function getSalesWithPagination(Customer $customer, Request $request): LengthAwarePaginator;

    /**
     * Check if customer exists by ID
     *
     * @param int $customerId
     * @return bool
     */
    public function exists(int $customerId): bool;
}

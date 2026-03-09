<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\SaleStatus;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

interface SaleRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @param int $id
     * @return Sale|null
     */
    public function findWithRelations(int $id): ?Sale;

    /**
     * @param Sale $sale
     * @param SaleStatus $status
     * @return bool
     */
    public function updateStatus(Sale $sale, SaleStatus $status): bool;

    /**
     * @param Request $request
     * @return LengthAwarePaginator
     */
    public function getPaginatedWithRelations(Request $request): LengthAwarePaginator;
}

<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface HasAssociationsInterface
{
    /**
     * @param Model $model
     * @return bool
     */
    public function hasAssociatedSales(Model $model): bool;

    /**
     * @param Model $model
     * @return bool
     */
    public function canBeDeleted(Model $model): bool;

    /**
     * @param Model $model
     * @return int
     */
    public function getAssociatedSalesCount(Model $model): int;

    /**
     * @return Collection
     */
    public function getModelsWithoutAssociations(): Collection;
}

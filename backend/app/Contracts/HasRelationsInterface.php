<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

interface HasRelationsInterface
{
    /**
     * @param int $id
     * @return Model|null
     */
    public function findWithRelations(int $id): ?Model;

    /**
     * @param int $id
     * @param array $relations
     * @return Model|null
     */
    public function findWith(int $id, array $relations): ?Model;

    /**
     * @param Request $request
     * @return LengthAwarePaginator
     */
    public function getPaginatedWithRelations(Request $request): LengthAwarePaginator;

    /**
     * @param array $relations
     * @return Collection
     */
    public function getAllWithRelations(array $relations = []): Collection;

    /**
     * @return array
     */
    public function getDefaultRelations(): array;

    /**
     * @param array $relations
     * @return void
     */
    public function setDefaultRelations(array $relations): void;
}

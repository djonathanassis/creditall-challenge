<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @template T of Model
 */
interface BaseRepositoryInterface
{
    /**
     * @param int $id
     * @return Model|null
     */
    public function find(int $id): ?Model;

    /**
     * @throws ModelNotFoundException
     */
    public function findOrFail(int $id): Model;

    /**
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model;

    /**
     * @param  T  $model
     */
    public function update(Model $model, array $data): bool;

    /**
     * @param  T  $model
     */
    public function delete(Model $model): bool;

    /**
     * @param Request $request
     * @return Builder
     */
    public function buildListingQuery(Request $request): Builder;

    /**
     * @param Request $request
     * @return LengthAwarePaginator
     */
    public function getPaginated(Request $request): LengthAwarePaginator;

    /**
     * @param string $query
     * @return Builder
     */
    public function search(string $query): Builder;

    /**
     * @param array $filters
     * @return Builder
     */
    public function filterBy(array $filters): Builder;
}

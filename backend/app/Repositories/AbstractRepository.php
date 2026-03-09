<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\BaseRepositoryInterface;
use App\Traits\Filterable;
use App\Traits\Searchable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @template T of Model
 *
 * @implements BaseRepositoryInterface<T>
 */
abstract class AbstractRepository implements BaseRepositoryInterface
{
    use Filterable, Searchable, Sortable;

    /**
     * @var class-string<T>
     */
    protected string $model;

    /**
     * @var int
     */
    protected int $defaultPerPage = 15;

    /**
     * @return T
     */
    protected function getModel(): Model
    {
        return new $this->model();
    }

    /**
     * @return Builder
     */
    protected function getQuery(): Builder
    {
        return $this->getModel()->query();
    }

    /**
     * {@inheritdoc}
     */
    public function find(int $id): ?Model
    {
        return $this->getQuery()->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function findOrFail(int $id): Model
    {
        return $this->getQuery()->findOrFail($id);
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): Model
    {
        return $this->getQuery()->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(Model $model, array $data): bool
    {
        return $model->update($data);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Model $model): bool
    {
        return $model->delete();
    }

    /**
     * Find model including soft deleted
     */
    public function findWithTrashed(int $id): ?Model
    {
        return $this->getModel()->withTrashed()->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function buildListingQuery(Request $request): Builder
    {
        $query = $this->getQuery();

        if ($search = $request->input('search')) {
            $query = $this->applySearch($query, $search);
        }

        $filters = $request->except(['search', 'sort_by', 'sort_order', 'per_page', 'page']);
        if (! empty($filters)) {
            $query = $this->applyFilters($query, $filters);
        }

        $query = $this->applySorting($query, $request);

        return $this->customizeListingQuery($query, $request);
    }

    /**
     * {@inheritdoc}
     */
    public function getPaginated(Request $request): LengthAwarePaginator
    {
        $query = $this->buildListingQuery($request);
        $perPage = (int) $request->input('per_page', $this->defaultPerPage);

        $perPage = max(1, min($perPage, 100));

        return $query->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function search(string $query): Builder
    {
        return $this->applySearch($this->getQuery(), $query);
    }

    /**
     * {@inheritdoc}
     */
    public function filterBy(array $filters): Builder
    {
        return $this->applyFilters($this->getQuery(), $filters);
    }

    /**
     * @param Builder $query
     * @param Request $request
     * @return Builder
     */
    protected function customizeListingQuery(Builder $query, Request $request): Builder
    {
        return $query;
    }

    /**
     * @return int
     */
    public function getDefaultPerPage(): int
    {
        return $this->defaultPerPage;
    }

    /**
     * @param int $perPage
     * @return void
     */
    public function setDefaultPerPage(int $perPage): void
    {
        $this->defaultPerPage = max(1, min($perPage, 100));
    }
}

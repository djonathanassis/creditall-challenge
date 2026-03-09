<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait Sortable
{
    /**
     * @var array<string>
     */
    protected array $sortableFields = [];

    protected string $defaultSort = 'created_at';

    protected string $defaultSortOrder = 'desc';

    protected function applySorting(Builder $query, Request $request): Builder
    {
        $sortBy = $request->get('sort_by', $this->defaultSort);
        $sortOrder = $request->get('sort_order', $this->defaultSortOrder);

        if (! in_array($sortBy, $this->sortableFields, true)) {
            $sortBy = $this->defaultSort;
        }

        if (! in_array(strtolower($sortOrder), ['asc', 'desc'], true)) {
            $sortOrder = $this->defaultSortOrder;
        }

        return $query->orderBy($sortBy, $sortOrder);
    }

    /**
     * @return array<string>
     */
    public function getSortableFields(): array
    {
        return $this->sortableFields;
    }

    /**
     * @return array{field: string, order: string}
     */
    public function getDefaultSort(): array
    {
        return [
            'field' => $this->defaultSort,
            'order' => $this->defaultSortOrder,
        ];
    }
}

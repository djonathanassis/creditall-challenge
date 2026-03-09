<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Searchable
{
    /**
     * @var array<string>
     */
    protected array $searchableFields = [];

    protected function applySearch(Builder $query, string $search): Builder
    {
        if (empty($this->searchableFields) || empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            foreach ($this->searchableFields as $field) {
                $q->orWhere($field, 'like', "%{$search}%");
            }
        });
    }

    /**
     * @return array<string>
     */
    public function getSearchableFields(): array
    {
        return $this->searchableFields;
    }
}

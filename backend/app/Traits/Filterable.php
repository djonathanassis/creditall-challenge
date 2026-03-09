<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Filterable
{
    protected array $filterableFields = [];

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        foreach ($filters as $field => $value) {
            if (! in_array($field, $this->filterableFields, true) || $value === null || $value === '') {
                continue;
            }

            $this->applyFilter($query, $field, $value);
        }

        return $query;
    }

    protected function applyFilter(Builder $query, string $field, mixed $value): void
    {
        if (is_array($value)) {
            $query->whereIn($field, $value);

            return;
        }

        [$actualField, $operator] = $this->resolveFieldAndOperator($field);
        $query->where($actualField, $operator, $value);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveFieldAndOperator(string $field): array
    {
        $suffixMap = [
            '_from' => '>=',
            '_start' => '>=',
            '_min' => '>=',
            '_to' => '<=',
            '_end' => '<=',
            '_max' => '<=',
        ];

        foreach ($suffixMap as $suffix => $operator) {
            if (str_ends_with($field, $suffix)) {
                return [str_replace($suffix, '', $field), $operator];
            }
        }

        return [$field, '='];
    }

    public function getFilterableFields(): array
    {
        return $this->filterableFields;
    }
}

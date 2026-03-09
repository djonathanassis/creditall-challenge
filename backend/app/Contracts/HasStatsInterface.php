<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Database\Eloquent\Model;

interface HasStatsInterface
{
    /**
     * @param int $id
     * @return Model|null
     */
    public function findWithStats(int $id): ?Model;

    /**
     * @return array
     */
    public function getStats(): array;

    /**
     * @param int|null $year
     * @return array
     */
    public function getMonthlyStats(?int $year = null): array;

    /**
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getStatsForDateRange(string $startDate, string $endDate): array;
}

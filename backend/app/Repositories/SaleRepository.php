<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\HasRelationsInterface;
use App\Contracts\HasStatsInterface;
use App\Contracts\SaleRepositoryInterface;
use App\Enums\SaleStatus;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class SaleRepository extends AbstractRepository implements HasRelationsInterface, HasStatsInterface, SaleRepositoryInterface
{
    /**
     * @var class-string<Sale>
     */
    protected string $model = Sale::class;

    /**
     * @var array<string>
     */
    protected array $filterableFields = [
        'customer_id',
        'status',
        'start_date',
        'end_date',
        'created_at_from',
        'created_at_to',
    ];

    /**
     * @var array<string>
     */
    protected array $sortableFields = ['total_amount', 'created_at', 'updated_at'];

    /**
     * @var array<string>
     */
    protected array $defaultRelations = ['customer', 'items.product'];

    /**
     * {@inheritdoc}
     */
    public function findWithRelations(int $id): ?Sale
    {
        return Sale::with($this->defaultRelations)->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function findWith(int $id, array $relations): ?Model
    {
        return Sale::with($relations)->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getPaginatedWithRelations(Request $request): LengthAwarePaginator
    {
        $query = $this->buildListingQuery($request);
        $perPage = (int) $request->get('per_page', $this->defaultPerPage);
        $perPage = max(1, min($perPage, 100));

        return $query->with($this->defaultRelations)->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getAllWithRelations(array $relations = []): Collection
    {
        $relations = empty($relations) ? $this->defaultRelations : $relations;

        return Sale::with($relations)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultRelations(): array
    {
        return $this->defaultRelations;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultRelations(array $relations): void
    {
        $this->defaultRelations = $relations;
    }

    /**
     * {@inheritdoc}
     */
    public function findWithStats(int $id): ?Model
    {
        return Sale::with(['customer', 'items.product'])
            ->withCount('items')
            ->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getStats(): array
    {
        return [
            'total_sales' => Sale::count(),
            'total_revenue' => Sale::sum('total_amount'),
            'avg_sale_amount' => Sale::avg('total_amount'),
            'pending_sales' => Sale::where('status', SaleStatus::PENDING)->count(),
            'completed_sales' => Sale::where('status', SaleStatus::COMPLETED)->count(),
            'cancelled_sales' => Sale::where('status', SaleStatus::CANCELLED)->count(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getMonthlyStats(?int $year = null): array
    {
        $year = $year ?? now()->year;

        return Sale::whereYear('created_at', $year)
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as count, SUM(total_amount) as revenue')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month')
            ->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getStatsForDateRange(string $startDate, string $endDate): array
    {
        return [
            'total_sales' => Sale::whereBetween('created_at', [$startDate, $endDate])->count(),
            'total_revenue' => Sale::whereBetween('created_at', [$startDate, $endDate])->sum('total_amount'),
            'avg_sale_amount' => Sale::whereBetween('created_at', [$startDate, $endDate])->avg('total_amount'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function updateStatus(Sale $sale, SaleStatus $status): bool
    {
        return $sale->update(['status' => $status]);
    }

    /**
     * {@inheritdoc}
     */
    protected function customizeListingQuery(Builder $query, Request $request): Builder
    {
        $query->with($this->defaultRelations);

        if ($startDate = $request->input('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate = $request->input('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return $query;
    }
}

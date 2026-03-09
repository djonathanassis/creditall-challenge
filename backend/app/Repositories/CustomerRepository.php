<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\CustomerRepositoryInterface;
use App\Contracts\HasAssociationsInterface;
use App\Contracts\HasStatsInterface;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerRepository extends AbstractRepository implements CustomerRepositoryInterface, HasAssociationsInterface, HasStatsInterface
{
    /**
     * @var class-string<Customer>
     */
    protected string $model = Customer::class;

    /**
     * @var array<string>
     */
    protected array $searchableFields = ['name', 'email', 'cpf'];

    /**
     * @var array<string>
     */
    protected array $sortableFields = ['name', 'email', 'created_at', 'updated_at'];

    /**
     * {@inheritdoc}
     */
    public function hasAssociatedSales(Model $model): bool
    {
        /** @var Customer $model */
        return $model->sales()->exists();
    }

    /**
     * {@inheritdoc}
     */
    public function canBeDeleted(Model $model): bool
    {
        return ! $this->hasAssociatedSales($model);
    }

    /**
     * {@inheritdoc}
     */
    public function getAssociatedSalesCount(Model $model): int
    {
        /** @var Customer $model */
        return $model->sales()->count();
    }

    /**
     * {@inheritdoc}
     */
    public function getModelsWithoutAssociations(): Collection
    {
        return Customer::whereDoesntHave('sales')->get();
    }

    /**
     * {@inheritdoc}
     */
    public function findWithStats(int $id): ?Model
    {
        return Customer::withCount('sales')
            ->with([
                'sales' => function ($query) {
                    $query->selectRaw('customer_id, SUM(total_amount) as total_spent, COUNT(*) as total_orders')
                        ->groupBy('customer_id');
                },
            ])
            ->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getStats(): array
    {
        return [
            'total_customers' => Customer::count(),
            'customers_with_sales' => Customer::has('sales')->count(),
            'customers_without_sales' => Customer::doesntHave('sales')->count(),
            'avg_orders_per_customer' => Customer::withCount('sales')
                ->get()
                ->avg('sales_count'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getMonthlyStats(?int $year = null): array
    {
        $year = $year ?? now()->year;

        return Customer::whereYear('created_at', $year)
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month')
            ->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getStatsForDateRange(string $startDate, string $endDate): array
    {
        return [
            'new_customers' => Customer::whereBetween('created_at', [$startDate, $endDate])->count(),
            'customers_with_purchases' => Customer::whereHas('sales', static function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            })->count(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function findWithSalesStats(int $id): ?Customer
    {
        return Customer::with('sales')->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getSalesWithPagination(Customer $customer, Request $request): LengthAwarePaginator
    {
        $perPage = min((int) $request->input('per_page', 15), 100);

        $query = $customer->sales()->with('items.product');

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function exists(int $customerId): bool
    {
        return $this->model::query()->where('id', $customerId)->exists();
    }

    /**
     * {@inheritdoc}
     */
    protected function customizeListingQuery(Builder $query, Request $request): Builder
    {
        if ($request->input('with_stats')) {
            $query->with('sales');
        }

        return $query;
    }
}

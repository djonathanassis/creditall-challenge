<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ProductRepositoryInterface;
use App\Contracts\SaleCalculationServiceInterface;
use App\Contracts\SaleRepositoryInterface;
use App\DataTransferObjects\Sale\CreateSaleDTO;
use App\DataTransferObjects\Sale\SaleItemDTO;
use App\DataTransferObjects\Sale\UpdateSaleStatusDTO;
use App\Enums\SaleStatus;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidStatusTransitionException;
use App\Exceptions\SaleException;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Throwable;

readonly class SaleService
{
    public function __construct(
        private SaleRepositoryInterface $saleRepository,
        private ProductRepositoryInterface $productRepository,
        private InventoryService $inventoryService,
        private SaleCalculationServiceInterface $saleCalculator,
        private SaleValidationService $validationService
    ) {}

    /**
     * @throws Throwable
     */
    public function deleteSale(Sale $sale): bool
    {
        return DB::transaction(function () use ($sale) {
            if ($sale->status === SaleStatus::COMPLETED) {
                $this->inventoryService->bulkReleaseStock($sale->items->all());
            }

            return $this->saleRepository->delete($sale);
        });
    }

    /**
     * @throws InvalidStatusTransitionException
     * @throws Throwable
     */
    public function updateStatus(Sale $sale, UpdateSaleStatusDTO $dto): Sale
    {
        return DB::transaction(function () use ($sale, $dto) {
            if (! $sale->status->canTransitionTo($dto->status)) {
                throw new InvalidStatusTransitionException($sale->status, $dto->status);
            }

            $this->handleInventoryOnStatusChange($sale, $sale->status, $dto->status);
            $this->saleRepository->updateStatus($sale, $dto->status);

            return $sale->fresh();
        });
    }

    /**
     * @param Request $request
     * @return LengthAwarePaginator
     */
    public function getPaginatedSales(Request $request): LengthAwarePaginator
    {
        return $this->saleRepository->getPaginatedWithRelations($request);
    }

    public function findSaleWithRelations(int $id): ?Sale
    {
        return $this->saleRepository->findWithRelations($id);
    }

    /**
     * @throws SaleException
     * @throws InsufficientStockException
     * @throws Throwable
     */
    public function createSale(CreateSaleDTO $dto): Sale
    {
        $enrichedDTO = $this->validationService->enrichAndValidate($dto);

        return DB::transaction(function () use ($enrichedDTO) {
            $this->inventoryService->bulkValidateStock($enrichedDTO->items);

            $totals = $this->saleCalculator->calculateSaleTotals(
                $enrichedDTO->items,
                $enrichedDTO->discountPercentage
            );

            /** @var Sale $sale */
            $sale = $this->saleRepository->create([
                'customer_id' => $enrichedDTO->customerId,
                'status' => SaleStatus::PENDING,
                'discount_percentage' => $enrichedDTO->discountPercentage,
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $totals['discount_amount'],
                'total_amount' => $totals['total_amount'],
            ]);

            $this->createItemsAndReserveStock($sale, $enrichedDTO);

            return $sale->fresh(['customer', 'items.product']);
        });
    }

    /**
     * @param Sale $sale
     * @param CreateSaleDTO $dto
     * @throws SaleException
     * @throws InsufficientStockException
     */
    private function createItemsAndReserveStock(Sale $sale, CreateSaleDTO $dto): void
    {
        foreach ($dto->items as $itemDto) {
            /** @var Product $product */
            $product = $this->productRepository->find($itemDto->productId);

            if (!$product) {
                throw new SaleException(
                    "Produto não encontrado (ID: {$itemDto->productId})"
                );
            }

            $sale->items()->create([
                'product_id' => $itemDto->productId,
                'quantity' => $itemDto->quantity,
                'unit_price' => $itemDto->unitPrice,
                'subtotal' => $this->saleCalculator->calculateItemSubtotal($itemDto),
            ]);

            // Reserve stock
            $this->inventoryService->reserveStock($product, $itemDto->quantity);
        }
    }

    /**
     * @throws InsufficientStockException
     */
    private function handleInventoryOnStatusChange(Sale $sale, SaleStatus $fromStatus, SaleStatus $toStatus): void
    {
        if ($toStatus === SaleStatus::CANCELLED && $fromStatus !== SaleStatus::CANCELLED) {
            $this->inventoryService->bulkReleaseStock($sale->items->all());
        }

        if ($fromStatus === SaleStatus::CANCELLED && $toStatus !== SaleStatus::CANCELLED) {
            $items = $sale->items->map(function ($item) {
                return SaleItemDTO::fromArray([
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                ]);
            })->toArray();

            $this->inventoryService->bulkValidateStock($items);
            $this->inventoryService->bulkReserveStock($items);
        }
    }
}

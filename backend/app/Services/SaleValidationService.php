<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\CustomerRepositoryInterface;
use App\Contracts\ProductPriceServiceInterface;
use App\Contracts\SaleCalculationServiceInterface;
use App\DataTransferObjects\Sale\CreateSaleDTO;
use App\DataTransferObjects\Sale\SaleItemDTO;
use App\Exceptions\SaleException;

readonly class SaleValidationService
{
    public function __construct(
        private ProductPriceServiceInterface $priceService,
        private SaleCalculationServiceInterface $calculator,
        private CustomerRepositoryInterface $customerRepository
    ) {}

    /**
     * @param CreateSaleDTO $dto
     * @return CreateSaleDTO
     * @throws SaleException
     */
    public function enrichAndValidate(CreateSaleDTO $dto): CreateSaleDTO
    {
        $enrichedDTO = $this->enrichDTOWithPrices($dto);
        $this->validateSaleCreation($enrichedDTO);

        return $enrichedDTO;
    }

    /**
     * @param CreateSaleDTO $dto
     * @return CreateSaleDTO
     * @throws SaleException
     */
    public function enrichDTOWithPrices(CreateSaleDTO $dto): CreateSaleDTO
    {
        $productIds = array_map(
            static fn (SaleItemDTO $item) => $item->productId,
            $dto->items
        );

        $prices = $this->priceService->getPricesForProducts($productIds);

        $enrichedItems = array_map(
            static fn (SaleItemDTO $item) => new SaleItemDTO(
                productId: $item->productId,
                quantity: $item->quantity,
                unitPrice: (float) ($prices[$item->productId] ?? throw new SaleException(
                    "Produto não encontrado (ID: {$item->productId})"
                ))
            ),
            $dto->items
        );

        return new CreateSaleDTO(
            customerId: $dto->customerId,
            discountPercentage: $dto->discountPercentage,
            items: $enrichedItems
        );
    }

    /**
     * @throws SaleException
     */
    public function validateSaleCreation(CreateSaleDTO $dto): void
    {
        if (! $this->customerRepository->exists($dto->customerId)) {
            throw new SaleException('Cliente não encontrado');
        }

        if ($dto->discountPercentage < 0 || $dto->discountPercentage > 100) {
            throw new SaleException('Desconto deve estar entre 0% e 100%');
        }

        if (empty($dto->items)) {
            throw new SaleException('Venda deve conter pelo menos um item');
        }

        foreach ($dto->items as $index => $item) {
            $this->validateSaleItem($item, $index);
        }

        $totals = $this->calculator->calculateSaleTotals($dto->items, $dto->discountPercentage);
        if ($totals['total_amount'] < 0.01) {
            throw new SaleException('Valor da venda deve ser maior que zero');
        }
    }

    /**
     * @throws SaleException
     */
    private function validateSaleItem(SaleItemDTO $item, int $index): void
    {
        if ($item->productId <= 0) {
            throw new SaleException("Item {$index}: ID do produto inválido");
        }

        if ($item->quantity <= 0) {
            throw new SaleException("Item {$index}: Quantidade deve ser maior que zero");
        }

        if ($item->unitPrice === null) {
            throw new SaleException("Item {$index}: Preço não foi enriquecido corretamente");
        }

        if ($item->unitPrice < 0) {
            throw new SaleException("Item {$index}: Preço não pode ser negativo");
        }

        if ($item->unitPrice === 0.0) {
            throw new SaleException("Item {$index}: Preço deve ser maior que zero");
        }
    }

    /**
     * @param SaleItemDTO[] $items
     * @return array<int, array{product_id: int, quantity: int}>
     */
    public function convertItemsToStockArray(array $items): array
    {
        return array_map(
            static fn (SaleItemDTO $item) => [
                'product_id' => $item->productId,
                'quantity' => $item->quantity,
            ],
            $items
        );
    }

    /**
     * @param SaleItemDTO[] $items
     * @return array<int>
     */
    public function getProductIds(array $items): array
    {
        return array_unique(array_map(
            static fn (SaleItemDTO $item) => $item->productId,
            $items
        ));
    }

    /**
     * @param SaleItemDTO[] $items
     */
    public function getTotalItemCount(array $items): int
    {
        return array_sum(array_map(
            static fn (SaleItemDTO $item) => $item->quantity,
            $items
        ));
    }
}

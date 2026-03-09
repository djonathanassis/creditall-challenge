<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Contracts\CustomerRepositoryInterface;
use App\Contracts\ProductPriceServiceInterface;
use App\Contracts\SaleCalculationServiceInterface;
use App\DataTransferObjects\Sale\CreateSaleDTO;
use App\DataTransferObjects\Sale\SaleItemDTO;
use App\Exceptions\SaleException;
use App\Services\SaleCalculationService;
use App\Services\SaleValidationService;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class SaleValidationServiceTest extends TestCase
{
    private SaleValidationService $service;
    private MockInterface $priceService;
    private MockInterface $calculator;
    private MockInterface $customerRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->priceService = Mockery::mock(ProductPriceServiceInterface::class);
        $this->calculator = Mockery::mock(SaleCalculationServiceInterface::class);
        $this->customerRepository = Mockery::mock(CustomerRepositoryInterface::class);

        $this->service = new SaleValidationService(
            $this->priceService,
            $this->calculator,
            $this->customerRepository
        );
    }

    public function testEnrichDTOWithPrices(): void
    {
        $items = [
            new SaleItemDTO(productId: 1, quantity: 2, unitPrice: 0),
            new SaleItemDTO(productId: 2, quantity: 1, unitPrice: 0),
        ];

        $dto = new CreateSaleDTO(
            customerId: 1,
            discountPercentage: 10,
            items: $items
        );

        $this->priceService
            ->allows('getPricesForProducts')
            ->with([1, 2])
            ->andReturns([1 => 100.00, 2 => 50.00]);

        $result = $this->service->enrichDTOWithPrices($dto);

        $this->assertEquals(100.00, $result->items[0]->unitPrice);
        $this->assertEquals(50.00, $result->items[1]->unitPrice);
    }

    public function testEnrichDTOWithPricesThrowsWhenProductNotFound(): void
    {
        $items = [
            new SaleItemDTO(productId: 999, quantity: 1, unitPrice: 0),
        ];

        $dto = new CreateSaleDTO(
            customerId: 1,
            discountPercentage: 0,
            items: $items
        );

        $this->priceService
            ->allows('getPricesForProducts')
            ->with([999])
            ->andReturns([]);

        $this->expectException(SaleException::class);
        $this->expectExceptionMessage('Produto não encontrado (ID: 999)');

        $this->service->enrichDTOWithPrices($dto);
    }

    public function testValidateSaleCreationThrowsWhenCustomerNotFound(): void
    {
        $items = [
            new SaleItemDTO(productId: 1, quantity: 2, unitPrice: 100.00),
        ];

        $dto = new CreateSaleDTO(
            customerId: 999,
            discountPercentage: 0,
            items: $items
        );

        $this->customerRepository
            ->allows('exists')
            ->with(999)
            ->andReturns(false);

        $this->calculator
            ->allows('calculateSaleTotals')
            ->andReturns(['subtotal' => 200.00, 'discount_amount' => 0, 'total_amount' => 200.00]);

        $this->expectException(SaleException::class);
        $this->expectExceptionMessage('Cliente não encontrado');

        $this->service->validateSaleCreation($dto);
    }

    public function testValidateSaleCreationThrowsWhenDiscountOutOfRange(): void
    {
        $items = [
            new SaleItemDTO(productId: 1, quantity: 1, unitPrice: 100.00),
        ];

        $dto = new CreateSaleDTO(
            customerId: 1,
            discountPercentage: 150,
            items: $items
        );

        $this->customerRepository
            ->allows('exists')
            ->with(1)
            ->andReturns(true);

        $this->expectException(SaleException::class);
        $this->expectExceptionMessage('Desconto deve estar entre 0% e 100%');

        $this->service->validateSaleCreation($dto);
    }

    public function testValidateSaleCreationThrowsWhenNoItems(): void
    {
        $dto = new CreateSaleDTO(
            customerId: 1,
            discountPercentage: 0,
            items: []
        );

        $this->customerRepository
            ->allows('exists')
            ->with(1)
            ->andReturns(true);

        $this->expectException(SaleException::class);
        $this->expectExceptionMessage('Venda deve conter pelo menos um item');

        $this->service->validateSaleCreation($dto);
    }

    public function testValidateSaleCreationThrowsWhenTotalIsZero(): void
    {
        $items = [
            new SaleItemDTO(productId: 1, quantity: 1, unitPrice: 10.00),
        ];

        $dto = new CreateSaleDTO(
            customerId: 1,
            discountPercentage: 100,
            items: $items
        );

        $this->customerRepository
            ->allows('exists')
            ->with(1)
            ->andReturns(true);

        $this->calculator
            ->allows('calculateSaleTotals')
            ->andReturns(['subtotal' => 10.00, 'discount_amount' => 10.00, 'total_amount' => 0.0]);

        $this->expectException(SaleException::class);
        $this->expectExceptionMessage('Valor da venda deve ser maior que zero');

        $this->service->validateSaleCreation($dto);
    }

    public function testValidateSaleItemThrowsWhenProductIdInvalid(): void
    {
        $item = new SaleItemDTO(productId: 0, quantity: 1, unitPrice: 100.00);

        $this->customerRepository
            ->allows('exists')
            ->with(1)
            ->andReturns(true);

        $this->expectException(SaleException::class);
        $this->expectExceptionMessage('Item 0: ID do produto inválido');

        $this->service->validateSaleCreation(new CreateSaleDTO(
            customerId: 1,
            discountPercentage: 0,
            items: [$item]
        ));
    }

    public function testValidateSaleItemThrowsWhenQuantityInvalid(): void
    {
        $item = new SaleItemDTO(productId: 1, quantity: 0, unitPrice: 100.00);

        $this->customerRepository
            ->allows('exists')
            ->with(1)
            ->andReturns(true);

        $this->expectException(SaleException::class);
        $this->expectExceptionMessage('Item 0: Quantidade deve ser maior que zero');

        $this->service->validateSaleCreation(new CreateSaleDTO(
            customerId: 1,
            discountPercentage: 0,
            items: [$item]
        ));
    }

    public function testValidateSaleItemThrowsWhenPriceNotEnriched(): void
    {
        $item = new SaleItemDTO(productId: 1, quantity: 1, unitPrice: null);

        $this->customerRepository
            ->allows('exists')
            ->with(1)
            ->andReturns(true);

        $this->expectException(SaleException::class);
        $this->expectExceptionMessage('Item 0: Preço não foi enriquecido corretamente');

        $this->service->validateSaleCreation(new CreateSaleDTO(
            customerId: 1,
            discountPercentage: 0,
            items: [$item]
        ));
    }

    public function testValidateSaleItemThrowsWhenPriceNegative(): void
    {
        $item = new SaleItemDTO(productId: 1, quantity: 1, unitPrice: -10.00);

        $this->customerRepository
            ->allows('exists')
            ->with(1)
            ->andReturns(true);

        $this->expectException(SaleException::class);
        $this->expectExceptionMessage('Item 0: Preço não pode ser negativo');

        $this->service->validateSaleCreation(new CreateSaleDTO(
            customerId: 1,
            discountPercentage: 0,
            items: [$item]
        ));
    }

    public function testValidateSaleItemThrowsWhenPriceZero(): void
    {
        $item = new SaleItemDTO(productId: 1, quantity: 1, unitPrice: 0.0);

        $this->customerRepository
            ->allows('exists')
            ->with(1)
            ->andReturns(true);

        $this->expectException(SaleException::class);
        $this->expectExceptionMessage('Item 0: Preço deve ser maior que zero');

        $this->service->validateSaleCreation(new CreateSaleDTO(
            customerId: 1,
            discountPercentage: 0,
            items: [$item]
        ));
    }

    public function testConvertItemsToStockArray(): void
    {
        $items = [
            new SaleItemDTO(productId: 1, quantity: 2, unitPrice: 100.00),
            new SaleItemDTO(productId: 2, quantity: 3, unitPrice: 50.00),
        ];

        $result = $this->service->convertItemsToStockArray($items);

        $this->assertEquals([
            ['product_id' => 1, 'quantity' => 2],
            ['product_id' => 2, 'quantity' => 3],
        ], $result);
    }

    public function testGetProductIds(): void
    {
        $items = [
            new SaleItemDTO(productId: 1, quantity: 2, unitPrice: 100.00),
            new SaleItemDTO(productId: 2, quantity: 3, unitPrice: 50.00),
            new SaleItemDTO(productId: 1, quantity: 1, unitPrice: 75.00),
        ];

        $result = $this->service->getProductIds($items);

        $this->assertEquals([1, 2], $result);
    }

    public function testGetTotalItemCount(): void
    {
        $items = [
            new SaleItemDTO(productId: 1, quantity: 2, unitPrice: 100.00),
            new SaleItemDTO(productId: 2, quantity: 3, unitPrice: 50.00),
        ];

        $result = $this->service->getTotalItemCount($items);

        $this->assertEquals(5, $result);
    }

    public function testEnrichAndValidateFullFlow(): void
    {
        $items = [
            new SaleItemDTO(productId: 1, quantity: 2, unitPrice: 0),
        ];

        $dto = new CreateSaleDTO(
            customerId: 1,
            discountPercentage: 10,
            items: $items
        );

        $this->priceService
            ->allows('getPricesForProducts')
            ->with([1])
            ->andReturns([1 => 100.00]);

        $this->customerRepository
            ->allows('exists')
            ->with(1)
            ->andReturns(true);

        $this->calculator
            ->allows('calculateSaleTotals')
            ->andReturns(['subtotal' => 200.00, 'discount_amount' => 20.00, 'total_amount' => 180.00]);

        $result = $this->service->enrichAndValidate($dto);

        $this->assertEquals(100.00, $result->items[0]->unitPrice);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Contracts\ProductRepositoryInterface;
use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Services\InventoryService;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    private InventoryService $service;
    private MockInterface $productRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->productRepository = Mockery::mock(ProductRepositoryInterface::class);
        $this->service = new InventoryService($this->productRepository);
    }

    public function testValidateStockReturnsTrueWhenSufficient(): void
    {
        $product = new Product(['stock_quantity' => 10]);

        $result = $this->service->validateStock($product, 5);

        $this->assertTrue($result);
    }

    public function testValidateStockReturnsFalseWhenInsufficient(): void
    {
        $product = new Product(['stock_quantity' => 3]);

        $result = $this->service->validateStock($product, 5);

        $this->assertFalse($result);
    }

    public function testValidateStockReturnsTrueWhenExactQuantity(): void
    {
        $product = new Product(['stock_quantity' => 5]);

        $result = $this->service->validateStock($product, 5);

        $this->assertTrue($result);
    }

    public function testBulkValidateStockPassesWhenAllItemsHaveStock(): void
    {
        $items = [
            (object) ['productId' => 1, 'quantity' => 2],
            (object) ['productId' => 2, 'quantity' => 3],
        ];

        $product1 = new Product(['id' => 1, 'name' => 'Product 1', 'stock_quantity' => 10]);
        $product2 = new Product(['id' => 2, 'name' => 'Product 2', 'stock_quantity' => 5]);

        $this->productRepository
            ->allows('find')
            ->with(1)
            ->andReturns($product1);

        $this->productRepository
            ->allows('find')
            ->with(2)
            ->andReturns($product2);

        $this->service->bulkValidateStock($items);

        $this->assertTrue(true);
    }

    public function testBulkValidateStockThrowsWhenProductNotFound(): void
    {
        $items = [
            (object) ['productId' => 999, 'quantity' => 1],
        ];

        $this->productRepository
            ->allows('find')
            ->with(999)
            ->andReturns(null);

        $this->expectException(InsufficientStockException::class);
        $this->expectExceptionMessage('Produto #999');

        $this->service->bulkValidateStock($items);
    }

    public function testBulkValidateStockThrowsWhenInsufficientStock(): void
    {
        $items = [
            (object) ['productId' => 1, 'quantity' => 10],
        ];

        $product = new Product(['id' => 1, 'name' => 'Test Product', 'stock_quantity' => 3]);

        $this->productRepository
            ->allows('find')
            ->with(1)
            ->andReturns($product);

        $this->expectException(InsufficientStockException::class);
        $this->expectExceptionMessage('Test Product');

        $this->service->bulkValidateStock($items);
    }

    public function testReserveStockDecrementsStock(): void
    {
        $product = new Product(['id' => 1, 'name' => 'Test Product', 'stock_quantity' => 10]);

        $this->productRepository
            ->expects('decrementStock')
            ->with($product, 5);

        $this->service->reserveStock($product, 5);
    }

    public function testReserveStockThrowsWhenInsufficientStock(): void
    {
        $product = new Product(['id' => 1, 'name' => 'Test Product', 'stock_quantity' => 3]);

        $this->expectException(InsufficientStockException::class);
        $this->expectExceptionMessage('Test Product');

        $this->service->reserveStock($product, 5);
    }

    public function testReleaseStockIncrementsStock(): void
    {
        $product = new Product(['id' => 1, 'name' => 'Test Product', 'stock_quantity' => 5]);

        $this->productRepository
            ->expects('incrementStock')
            ->with($product, 3);

        $this->service->releaseStock($product, 3);
    }

    public function testBulkReserveStockPassesForAllItems(): void
    {
        $items = [
            (object) ['productId' => 1, 'quantity' => 2],
            (object) ['productId' => 2, 'quantity' => 3],
        ];

        $product1 = new Product(['id' => 1, 'name' => 'Product 1', 'stock_quantity' => 10]);
        $product2 = new Product(['id' => 2, 'name' => 'Product 2', 'stock_quantity' => 5]);

        $this->productRepository
            ->allows('find')
            ->with(1)
            ->andReturns($product1);

        $this->productRepository
            ->shouldReceive('find')
            ->with(2)
            ->andReturn($product2);

        $this->productRepository
            ->expects('decrementStock')
            ->twice();

        $this->service->bulkReserveStock($items);
    }

    public function testBulkReserveStockThrowsWhenProductNotFound(): void
    {
        $items = [
            (object) ['productId' => 999, 'quantity' => 1],
        ];

        $this->productRepository
            ->allows('find')
            ->with(999)
            ->andReturns(null);

        $this->expectException(InsufficientStockException::class);

        $this->service->bulkReserveStock($items);
    }

    public function testBulkReleaseStockIncrementsStockForAllItems(): void
    {
        $product1 = new Product(['id' => 1, 'name' => 'Product 1', 'stock_quantity' => 5]);
        $product2 = new Product(['id' => 2, 'name' => 'Product 2', 'stock_quantity' => 3]);

        $items = [
            (object) ['product' => $product1, 'quantity' => 2],
            (object) ['product' => $product2, 'quantity' => 1],
        ];

        $this->productRepository
            ->expects('incrementStock')
            ->with($product1, 2);

        $this->productRepository
            ->expects('incrementStock')
            ->with($product2, 1);

        $this->service->bulkReleaseStock($items);
    }
}

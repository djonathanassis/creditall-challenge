<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Contracts\ProductRepositoryInterface;
use App\DataTransferObjects\Product\CreateProductDTO;
use App\DataTransferObjects\Product\UpdateProductDTO;
use App\Exceptions\ProductException;
use App\Models\Product;
use App\Services\ImageUploadService;
use App\Services\ProductService;
use Illuminate\Http\UploadedFile;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class ProductServiceTest extends TestCase
{
    private ProductService $service;
    private MockInterface $productRepository;
    private MockInterface $imageUploadService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->productRepository = Mockery::mock(ProductRepositoryInterface::class);
        $this->imageUploadService = Mockery::mock(ImageUploadService::class);
        $this->service = new ProductService($this->productRepository, $this->imageUploadService);
    }

    public function testCreateProduct(): void
    {
        $dto = new CreateProductDTO(
            name: 'Test Product',
            description: 'Test description',
            price: 100.00,
            stockQuantity: 10
        );

        $product = new Product([
            'id' => 1,
            'name' => 'Test Product',
            'description' => 'Test description',
            'price' => 100.00,
            'stock_quantity' => 10,
        ]);

        $this->productRepository
            ->allows('create')
            ->with($dto->toArray())
            ->andReturns($product);

        $result = $this->service->createProduct($dto);

        $this->assertEquals('Test Product', $result->name);
    }

    public function testCreateProductWithImage(): void
    {
        $dto = new CreateProductDTO(
            name: 'Test Product',
            description: 'Test description',
            price: 100.00,
            stockQuantity: 10,
            image: Mockery::mock(UploadedFile::class)
        );

        $product = new Product([
            'id' => 1,
            'name' => 'Test Product',
            'description' => 'Test description',
            'price' => 100.00,
            'stock_quantity' => 10,
            'image_path' => 'images/product.jpg',
        ]);

        $this->imageUploadService
            ->expects('upload')
            ->andReturns('images/product.jpg');

        $this->productRepository
            ->expects('create')
            ->andReturnUsing(function ($data) use ($product) {
                $product->image_path = $data['image_path'] ?? null;
                return $product;
            });

        $result = $this->service->createProduct($dto);

        $this->assertEquals('Test Product', $result->name);
        $this->assertEquals('images/product.jpg', $result->image_path);
    }

    public function testUpdateProduct(): void
    {
        $this->markTestSkipped('Requires database for fresh() method');
    }

    public function testUpdateProductThrowsWhenNoUpdates(): void
    {
        $product = new Product(['id' => 1, 'name' => 'Test Product']);
        $dto = new UpdateProductDTO();

        $this->expectException(ProductException::class);
        $this->expectExceptionMessage('Nenhuma atualização foi fornecida para o produto');

        $this->service->updateProduct($product, $dto);
    }

    public function testDeleteProduct(): void
    {
        $product = new Product(['id' => 1, 'name' => 'Test Product', 'image_path' => null]);

        $this->productRepository
            ->allows('hasAssociatedSales')
            ->with($product)
            ->andReturns(false);

        $this->productRepository
            ->allows('delete')
            ->with($product)
            ->andReturns(true);

        $result = $this->service->deleteProduct($product);

        $this->assertTrue($result);
    }

    public function testDeleteProductThrowsWhenHasSales(): void
    {
        $product = new Product(['id' => 1, 'name' => 'Test Product']);

        $this->productRepository
            ->allows('hasAssociatedSales')
            ->with($product)
            ->andReturns(true);

        $this->expectException(ProductException::class);
        $this->expectExceptionMessage('Não é possível excluir produto com vendas associadas');

        $this->service->deleteProduct($product);
    }

    public function testDeleteProductDeletesImage(): void
    {
        $product = new Product(['id' => 1, 'name' => 'Test Product', 'image_path' => 'images/product.jpg']);

        $this->productRepository
            ->allows('hasAssociatedSales')
            ->with($product)
            ->andReturns(false);

        $this->imageUploadService
            ->expects('delete')
            ->with('images/product.jpg');

        $this->productRepository
            ->allows('delete')
            ->with($product)
            ->andReturns(true);

        $result = $this->service->deleteProduct($product);

        $this->assertTrue($result);
    }

    public function testAdjustStockIncreases(): void
    {
        $this->markTestSkipped('Requires database for fresh() method');
    }

    public function testAdjustStockDecreases(): void
    {
        $this->markTestSkipped('Requires database for fresh() method');
    }

    public function testAdjustStockThrowsWhenZero(): void
    {
        $product = new Product(['id' => 1, 'name' => 'Test Product', 'stock_quantity' => 10]);

        $this->expectException(ProductException::class);
        $this->expectExceptionMessage('Ajuste de estoque deve ser diferente de zero');

        $this->service->adjustStock($product, 0);
    }

    public function testAdjustStockThrowsWhenReducingBelowZero(): void
    {
        $product = new Product(['id' => 1, 'name' => 'Test Product', 'stock_quantity' => 5]);

        $this->expectException(ProductException::class);
        $this->expectExceptionMessage('Não é possível reduzir estoque abaixo de zero');

        $this->service->adjustStock($product, -10);
    }

    public function testGetProductWithStats(): void
    {
        $product = new Product(['id' => 1, 'name' => 'Test Product']);

        $this->productRepository
            ->allows('find')
            ->with(1)
            ->andReturns($product);

        $result = $this->service->getProductWithStats(1);

        $this->assertEquals('Test Product', $result->name);
    }

    public function testCanDeleteReturnsTrueWhenNoSales(): void
    {
        $product = new Product(['id' => 1, 'name' => 'Test Product']);

        $this->productRepository
            ->allows('hasAssociatedSales')
            ->with($product)
            ->andReturns(false);

        $result = $this->service->canDelete($product);

        $this->assertTrue($result);
    }

    public function testCanDeleteReturnsFalseWhenHasSales(): void
    {
        $product = new Product(['id' => 1, 'name' => 'Test Product']);

        $this->productRepository
            ->allows('hasAssociatedSales')
            ->with($product)
            ->andReturns(true);

        $result = $this->service->canDelete($product);

        $this->assertFalse($result);
    }
}

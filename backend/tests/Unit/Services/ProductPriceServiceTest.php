<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Contracts\ProductPriceServiceInterface;
use App\Exceptions\SaleException;
use App\Models\Product;
use App\Services\ProductPriceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPriceServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductPriceServiceInterface $priceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->priceService = new ProductPriceService();
    }

    public function testImplementsPriceServiceInterface(): void
    {
        $this->assertInstanceOf(ProductPriceServiceInterface::class, $this->priceService);
    }

    public function testGetPriceForSingleProductSuccess(): void
    {
        $product = Product::factory()->create(['price' => 99.99]);

        $price = $this->priceService->getPriceForProduct($product->id);

        $this->assertEquals(99.99, $price);
    }

    public function testGetPriceForSingleProductThrowsExceptionWhenNotFound(): void
    {
        $this->expectException(SaleException::class);
        $this->expectExceptionMessage('Produto não encontrado (ID: 999)');

        $this->priceService->getPriceForProduct(999);
    }

    public function testGetPricesForMultipleProductsSuccess(): void
    {
        $product1 = Product::factory()->create(['price' => 19.99]);
        $product2 = Product::factory()->create(['price' => 29.99]);
        $product3 = Product::factory()->create(['price' => 39.99]);

        $productIds = [$product1->id, $product2->id, $product3->id];
        $prices = $this->priceService->getPricesForProducts($productIds);

        $this->assertCount(3, $prices);
        $this->assertEquals(19.99, $prices[$product1->id]);
        $this->assertEquals(29.99, $prices[$product2->id]);
        $this->assertEquals(39.99, $prices[$product3->id]);
    }

    public function testGetPricesForMultipleProductsThrowsExceptionWhenSomeNotFound(): void
    {
        $product1 = Product::factory()->create(['price' => 19.99]);

        $productIds = [$product1->id, 999, 998];

        $this->expectException(SaleException::class);
        $this->expectExceptionMessage('Produtos não encontrados: 999, 998');

        $this->priceService->getPricesForProducts($productIds);
    }

    public function testGetPricesForEmptyArrayReturnsEmptyArray(): void
    {
        $prices = $this->priceService->getPricesForProducts([]);

        $this->assertEmpty($prices);
    }

    public function testCacheWorksCorrectly(): void
    {
        $product1 = Product::factory()->create(['price' => 19.99]);
        $product2 = Product::factory()->create(['price' => 29.99]);

        $prices1 = $this->priceService->getPricesForProducts([$product1->id, $product2->id]);

        if ($this->priceService instanceof ProductPriceService) {
            $cacheState = $this->priceService->getCacheState();
            $this->assertCount(2, $cacheState);
            $this->assertEquals(19.99, $cacheState[$product1->id]);
            $this->assertEquals(29.99, $cacheState[$product2->id]);
        }

        $prices2 = $this->priceService->getPricesForProducts([$product1->id]);

        $this->assertEquals($prices1[$product1->id], $prices2[$product1->id]);
        $this->assertEquals(19.99, $prices2[$product1->id]);
    }

    public function testCacheHandlesPartialMissCorrectly(): void
    {
        $product1 = Product::factory()->create(['price' => 19.99]);
        $product2 = Product::factory()->create(['price' => 29.99]);
        $product3 = Product::factory()->create(['price' => 39.99]);

        $this->priceService->getPriceForProduct($product1->id);

        $prices = $this->priceService->getPricesForProducts([
            $product1->id,
            $product2->id,
            $product3->id
        ]);

        $this->assertCount(3, $prices);
        $this->assertEquals(19.99, $prices[$product1->id]);
        $this->assertEquals(29.99, $prices[$product2->id]);
        $this->assertEquals(39.99, $prices[$product3->id]);
    }

    public function testCacheHandlesDuplicateProductIdsCorrectly(): void
    {
        $product = Product::factory()->create(['price' => 19.99]);
        $prices = $this->priceService->getPricesForProducts([
            $product->id,
            $product->id,
            $product->id
        ]);

        $this->assertCount(1, $prices);
        $this->assertEquals(19.99, $prices[$product->id]);
    }

    public function testSingleProductCallUsesCache(): void
    {
        $product = Product::factory()->create(['price' => 19.99]);

        $price1 = $this->priceService->getPriceForProduct($product->id);

        $price2 = $this->priceService->getPriceForProduct($product->id);

        $this->assertEquals($price1, $price2);
        $this->assertEquals(19.99, $price2);
    }

    public function testClearCacheMethod(): void
    {
        $product = Product::factory()->create(['price' => 19.99]);
        $this->priceService->getPriceForProduct($product->id);

        if ($this->priceService instanceof ProductPriceService) {
            $cacheStateBefore = $this->priceService->getCacheState();
            $this->assertCount(1, $cacheStateBefore);

            // Clear cache
            $this->priceService->clearCache();

            $cacheStateAfter = $this->priceService->getCacheState();
            $this->assertCount(0, $cacheStateAfter);
        } else {
            $this->assertTrue(true);
        }
    }

    public function testHandlesFloatingPointPricesCorrectly(): void
    {
        $product = Product::factory()->create(['price' => 19.995]);

        $price = $this->priceService->getPriceForProduct($product->id);

        $this->assertEquals(19.995, $price);
    }

    public function testHandlesZeroPriceProduct(): void
    {
        $product = Product::factory()->create(['price' => 0.0]);

        $price = $this->priceService->getPriceForProduct($product->id);

        $this->assertEquals(0.0, $price);
    }

    public function testBatchPriceRetrievalPerformance(): void
    {
        $products = Product::factory()->count(100)->create();
        $productIds = $products->pluck('id')->toArray();

        $startTime = microtime(true);
        $prices = $this->priceService->getPricesForProducts($productIds);
        $endTime = microtime(true);

        $this->assertCount(100, $prices);
        $this->assertLessThan(1.0, $endTime - $startTime);

        foreach ($products as $product) {
            $this->assertArrayHasKey($product->id, $prices);
            $this->assertEquals($product->price, $prices[$product->id]);
        }
    }
}

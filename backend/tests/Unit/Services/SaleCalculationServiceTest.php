<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Contracts\SaleCalculationServiceInterface;
use App\DataTransferObjects\Sale\SaleItemDTO;
use App\Services\SaleCalculationService;
use Tests\TestCase;

class SaleCalculationServiceTest extends TestCase
{
    private SaleCalculationServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SaleCalculationService();
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(SaleCalculationServiceInterface::class, $this->service);
    }

    public function testCalculatesItemSubtotalCorrectly(): void
    {
        $item = new SaleItemDTO(
            productId: 1,
            quantity: 2,
            unitPrice: 100.00
        );

        $subtotal = $this->service->calculateItemSubtotal($item);

        $this->assertEquals(200.00, $subtotal);
    }

    public function testCalculatesItemSubtotalWithZeroQuantity(): void
    {
        $item = new SaleItemDTO(
            productId: 1,
            quantity: 0,
            unitPrice: 25.50
        );

        $subtotal = $this->service->calculateItemSubtotal($item);

        $this->assertEquals(0.0, $subtotal);
    }

    public function testCalculatesDiscountAmountCorrectly(): void
    {
        $subtotal = 100.00;
        $discount = $this->service->calculateDiscountAmount($subtotal, 10);

        $this->assertEquals(10.00, $discount);
    }

    public function testCalculatesDiscountAmountWithZeroPercentage(): void
    {
        $subtotal = 100.00;
        $discount = $this->service->calculateDiscountAmount($subtotal, 0);

        $this->assertEquals(0.0, $discount);
    }

    public function testCalculatesDiscountAmountWithNegativePercentage(): void
    {
        $subtotal = 100.00;
        $discount = $this->service->calculateDiscountAmount($subtotal, -10);

        $this->assertEquals(-10.0, $discount);
    }

    public function testCalculatesDiscountAmountWithHundredPercent(): void
    {
        $subtotal = 100.00;
        $discount = $this->service->calculateDiscountAmount($subtotal, 100);

        $this->assertEquals(100.00, $discount);
    }

    public function testCalculatesSaleTotalsWithSingleItem(): void
    {
        $items = [
            new SaleItemDTO(
                productId: 1,
                quantity: 2,
                unitPrice: 100.00
            ),
        ];

        $totals = $this->service->calculateSaleTotals($items, 10);

        $this->assertEquals(200.00, $totals['subtotal']);
        $this->assertEquals(20.00, $totals['discount_amount']);
        $this->assertEquals(180.00, $totals['total_amount']);
    }

    public function testCalculatesSaleTotalsWithMultipleItems(): void
    {
        $items = [
            new SaleItemDTO(
                productId: 1,
                quantity: 2,
                unitPrice: 100.00
            ),
            new SaleItemDTO(
                productId: 2,
                quantity: 3,
                unitPrice: 50.00
            ),
        ];

        $totals = $this->service->calculateSaleTotals($items, 20);

        $this->assertEquals(350.00, $totals['subtotal']);
        $this->assertEquals(70.00, $totals['discount_amount']);
        $this->assertEquals(280.00, $totals['total_amount']);
    }

    public function testCalculatesSaleTotalsWithoutDiscount(): void
    {
        $items = [
            new SaleItemDTO(
                productId: 1,
                quantity: 1,
                unitPrice: 100.00
            ),
        ];

        $totals = $this->service->calculateSaleTotals($items, 0);

        $this->assertEquals(100.00, $totals['subtotal']);
        $this->assertEquals(0.0, $totals['discount_amount']);
        $this->assertEquals(100.00, $totals['total_amount']);
    }

    public function testCalculatesSaleTotalsWithEmptyItems(): void
    {
        $totals = $this->service->calculateSaleTotals([], 10);

        $this->assertEquals(0.0, $totals['subtotal']);
        $this->assertEquals(0.0, $totals['discount_amount']);
        $this->assertEquals(0.0, $totals['total_amount']);
    }

    public function testCalculatesWithFloatingPointPrecision(): void
    {
        $items = [
            new SaleItemDTO(
                productId: 1,
                quantity: 3,
                unitPrice: 19.99
            ),
        ];

        $totals = $this->service->calculateSaleTotals($items, 7.5);

        $this->assertEquals(59.97, $totals['subtotal']);
        $this->assertEqualsWithDelta(4.50, $totals['discount_amount'], 0.01);
        $this->assertEqualsWithDelta(55.47, $totals['total_amount'], 0.01);
    }

    public function testCalculatesComplexScenario(): void
    {
        $items = [
            new SaleItemDTO(
                productId: 1,
                quantity: 5,
                unitPrice: 12.99
            ),
            new SaleItemDTO(
                productId: 2,
                quantity: 2,
                unitPrice: 25.50
            ),
            new SaleItemDTO(
                productId: 3,
                quantity: 1,
                unitPrice: 99.99
            ),
        ];

        $totals = $this->service->calculateSaleTotals($items, 15);

        $this->assertEquals(215.94, $totals['subtotal']);
        $this->assertEqualsWithDelta(32.39, $totals['discount_amount'], 0.01);
        $this->assertEqualsWithDelta(183.55, $totals['total_amount'], 0.01);
    }
}

<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\ProductPriceServiceInterface;
use App\Contracts\ProductRepositoryInterface;
use App\Contracts\SaleCalculationServiceInterface;
use App\Contracts\SaleRepositoryInterface;
use App\Repositories\ProductRepository;
use App\Repositories\SaleRepository;
use App\Services\InventoryService;
use App\Services\ProductPriceService;
use App\Services\SaleCalculationService;
use App\Services\SaleValidationService;
use Illuminate\Support\ServiceProvider;

class BusinessServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Repository Bindings - Business Domain
        $this->app->singleton(
            SaleRepositoryInterface::class,
            SaleRepository::class
        );

        $this->app->singleton(
            ProductRepositoryInterface::class,
            ProductRepository::class
        );

        // Service Bindings - Calculation & Pricing (with interfaces)
        $this->app->singleton(
            ProductPriceServiceInterface::class,
            ProductPriceService::class
        );

        $this->app->singleton(
            SaleCalculationServiceInterface::class,
            SaleCalculationService::class
        );

        // Service Bindings - Sale Dependencies (concrete classes)
        $this->app->singleton(InventoryService::class);
        $this->app->singleton(SaleValidationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
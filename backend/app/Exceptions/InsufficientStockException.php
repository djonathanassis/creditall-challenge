<?php

declare(strict_types=1);

namespace App\Exceptions;

class InsufficientStockException extends SaleException
{
    public function __construct(string $productName, int $requested, int $available)
    {
        $message = "Estoque insuficiente para {$productName}. Solicitado: {$requested}, Disponível: {$available}";
        parent::__construct($message);
    }
}

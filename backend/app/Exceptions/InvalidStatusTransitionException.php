<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\SaleStatus;

class InvalidStatusTransitionException extends SaleException
{
    public function __construct(SaleStatus $from, SaleStatus $to)
    {
        $message = "Não é possível alterar status da venda de {$from->value} para {$to->value}";
        parent::__construct($message);
    }
}

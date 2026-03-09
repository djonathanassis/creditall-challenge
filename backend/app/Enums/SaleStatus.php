<?php

declare(strict_types=1);

namespace App\Enums;

enum SaleStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendente',
            self::COMPLETED => 'Concluído',
            self::CANCELLED => 'Cancelado',
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::PENDING => in_array($status, [self::COMPLETED, self::CANCELLED], true),
            self::COMPLETED => $status === self::CANCELLED,
            self::CANCELLED => $status === self::PENDING,
        };
    }
}

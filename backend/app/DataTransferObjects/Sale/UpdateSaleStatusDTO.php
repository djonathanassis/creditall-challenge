<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Sale;

use App\DataTransferObjects\Shared\BaseDTO;
use App\Enums\SaleStatus;
use App\Exceptions\SaleException;

final class UpdateSaleStatusDTO extends BaseDTO
{
    public function __construct(
        public readonly SaleStatus $status
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return static
     *
     * @throws SaleException
     */
    public static function fromArray(array $data): self
    {
        try {
            $status = SaleStatus::from($data['status']);
        } catch (\ValueError $e) {
            throw new SaleException('Status inválido');
        }

        return new self(status: $status);
    }

    public function getStatusValue(): string
    {
        return $this->status->value;
    }

    /**
     * @return string Status label
     */
    public function getStatusLabel(): string
    {
        return $this->status->label();
    }
}

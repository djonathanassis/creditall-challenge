<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Sale;

use App\DataTransferObjects\Shared\BaseDTO;
use App\Enums\SaleStatus;
use App\Exceptions\SaleException;

final class SaleFilterDTO extends BaseDTO
{
    public function __construct(
        public readonly ?SaleStatus $status = null,
        public readonly ?int $customerId = null,
        public readonly ?string $startDate = null,
        public readonly ?string $endDate = null,
        public readonly ?float $minAmount = null,
        public readonly ?float $maxAmount = null,
        public readonly int $perPage = 15,
        public readonly int $page = 1
    ) {}

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws SaleException
     */
    public static function fromArray(array $data): self
    {
        $status = null;
        if (isset($data['status']) && $data['status'] !== '') {
            try {
                $status = SaleStatus::from($data['status']);
            } catch (\ValueError $e) {
                throw new SaleException('Status de filtro inválido');
            }
        }

        $customerId = null;
        if (isset($data['customer_id']) && $data['customer_id'] !== '') {
            $customerId = (int) $data['customer_id'];
            if ($customerId <= 0) {
                throw new SaleException('ID do cliente deve ser maior que zero');
            }
        }

        $startDate = isset($data['start_date']) && $data['start_date'] !== ''
            ? $data['start_date']
            : null;
        $endDate = isset($data['end_date']) && $data['end_date'] !== ''
            ? $data['end_date']
            : null;

        $minAmount = isset($data['min_amount']) && $data['min_amount'] !== ''
            ? (float) $data['min_amount']
            : null;
        $maxAmount = isset($data['max_amount']) && $data['max_amount'] !== ''
            ? (float) $data['max_amount']
            : null;

        if ($minAmount !== null && $maxAmount !== null && $minAmount > $maxAmount) {
            throw new SaleException('Valor mínimo não pode ser maior que valor máximo');
        }

        $perPage = isset($data['per_page']) ? (int) $data['per_page'] : 15;
        $perPage = max(1, min($perPage, 100));

        $page = isset($data['page']) ? (int) $data['page'] : 1;
        $page = max(1, $page);

        return new self(
            status: $status,
            customerId: $customerId,
            startDate: $startDate,
            endDate: $endDate,
            minAmount: $minAmount,
            maxAmount: $maxAmount,
            perPage: $perPage,
            page: $page
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toQueryArray(): array
    {
        $query = [];

        if ($this->status !== null) {
            $query['status'] = $this->status->value;
        }

        if ($this->customerId !== null) {
            $query['customer_id'] = $this->customerId;
        }

        if ($this->startDate !== null) {
            $query['start_date'] = $this->startDate;
        }

        if ($this->endDate !== null) {
            $query['end_date'] = $this->endDate;
        }

        if ($this->minAmount !== null) {
            $query['min_amount'] = $this->minAmount;
        }

        if ($this->maxAmount !== null) {
            $query['max_amount'] = $this->maxAmount;
        }

        $query['per_page'] = $this->perPage;
        $query['page'] = $this->page;

        return $query;
    }

    public function hasFilters(): bool
    {
        return $this->status !== null
            || $this->customerId !== null
            || $this->startDate !== null
            || $this->endDate !== null
            || $this->minAmount !== null
            || $this->maxAmount !== null;
    }

    /**
     * @return array{per_page: int, page: int}
     */
    public function getPagination(): array
    {
        return [
            'per_page' => $this->perPage,
            'page' => $this->page,
        ];
    }
}

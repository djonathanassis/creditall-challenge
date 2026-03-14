<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Customer;

use App\DataTransferObjects\Shared\BaseDTO;

final class UpdateCustomerDTO extends BaseDTO
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $email = null,
        public readonly ?string $cpf = null,
        public readonly ?string $phone = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return static
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            email: $data['email'] ?? null,
            cpf: isset($data['cpf']) ? self::cleanCpf($data['cpf']) : null,
            phone: isset($data['phone']) ? self::cleanPhone($data['phone']) : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $array = [];

        if ($this->name !== null) {
            $array['name'] = $this->name;
        }

        if ($this->email !== null) {
            $array['email'] = $this->email;
        }

        if ($this->cpf !== null) {
            $array['cpf'] = $this->cpf;
        }

        if ($this->phone !== null) {
            $array['phone'] = $this->phone;
        }

        return $array;
    }

    public function hasUpdates(): bool
    {
        return ! empty($this->toArray());
    }

    public static function cleanCpf(string $cpf): string
    {
        return preg_replace('/\D/', '', $cpf);
    }

    public static function cleanPhone(string $phone): string
    {
        return preg_replace('/\D/', '', $phone);
    }
}

<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Customer;

use App\DataTransferObjects\Shared\BaseDTO;

final class CreateCustomerDTO extends BaseDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $cpf,
        public readonly string $phone,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return static
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            cpf: self::cleanCpf($data['cpf']),
            phone: self::cleanPhone($data['phone']),
        );
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

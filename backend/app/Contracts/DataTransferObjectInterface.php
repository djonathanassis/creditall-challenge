<?php

declare(strict_types=1);

namespace App\Contracts;

interface DataTransferObjectInterface
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * @param  array<string, mixed>  $data
     * @return static
     */
    public static function fromArray(array $data): self;

    /**
     * @throws \JsonException
     */
    public function toJson(int $flags = 0): string;
}

<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Shared;

use App\Contracts\DataTransferObjectInterface;
use Illuminate\Support\Arr;

abstract class BaseDTO implements DataTransferObjectInterface, \JsonSerializable
{
    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        $reflection = new \ReflectionClass($this);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

        $array = [];
        foreach ($properties as $property) {
            $name = $this->convertPropertyNameToArrayKey($property->getName());
            $value = $property->getValue($this);

            if ($value instanceof DataTransferObjectInterface) {
                $value = $value->toArray();
            }

            $array[$name] = $value;
        }

        return $array;
    }

    /**
     * {@inheritDoc}
     */
    public function toJson(int $flags = 0): string
    {
        return json_encode($this->toArray(), $flags | JSON_THROW_ON_ERROR);
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @param  array<string>  $fields
     * @return array<string, mixed>
     */
    public function only(array $fields): array
    {
        return Arr::only($this->toArray(), $fields);
    }

    /**
     * @param  array<string>  $fields
     * @return array<string, mixed>
     */
    public function except(array $fields): array
    {
        return Arr::except($this->toArray(), $fields);
    }

    protected function convertPropertyNameToArrayKey(string $propertyName): string
    {
        return str_replace(' ', '_', strtolower(preg_replace('/([A-Z])/', ' $1', $propertyName)));
    }
}

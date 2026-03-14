<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DataTransferObjects\Customer\CreateCustomerDTO;
use App\DataTransferObjects\Customer\UpdateCustomerDTO;
use App\DataTransferObjects\Product\CreateProductDTO;
use App\DataTransferObjects\Product\UpdateProductDTO;
use PHPUnit\Framework\TestCase;

class DataTransferObjectsTest extends TestCase
{
    public function testCreateProductDtoFromArray(): void
    {
        $data = [
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 19.99,
            'stock_quantity' => 10,
        ];

        $dto = CreateProductDTO::fromArray($data);

        $this->assertEquals('Test Product', $dto->name);
        $this->assertEquals('Test Description', $dto->description);
        $this->assertEquals(19.99, $dto->price);
        $this->assertEquals(10, $dto->stockQuantity);
        $this->assertNull($dto->image);
    }

    public function testCreateProductDtoToArray(): void
    {
        $data = [
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 19.99,
            'stock_quantity' => 10,
        ];

        $dto = CreateProductDTO::fromArray($data);
        $result = $dto->toArray();

        $this->assertEquals($data['name'], $result['name']);
        $this->assertEquals($data['description'], $result['description']);
        $this->assertEquals($data['price'], $result['price']);
        $this->assertEquals($data['stock_quantity'], $result['stock_quantity']);
        $this->assertNull($result['image']);
    }

    public function testUpdateProductDtoPartialUpdate(): void
    {
        $data = [
            'name' => 'Updated Product',
            'price' => 29.99,
        ];

        $dto = UpdateProductDTO::fromArray($data);

        $this->assertEquals('Updated Product', $dto->name);
        $this->assertEquals(29.99, $dto->price);
        $this->assertNull($dto->stockQuantity);
        $this->assertNull($dto->description);
        $this->assertTrue($dto->hasUpdates());
    }

    public function testUpdateProductDtoToArrayOnlyNonNull(): void
    {
        $data = [
            'name' => 'Updated Product',
            'price' => 29.99,
        ];

        $dto = UpdateProductDTO::fromArray($data);
        $result = $dto->toArray();

        $this->assertEquals([
            'name' => 'Updated Product',
            'price' => 29.99,
        ], $result);

        $this->assertArrayNotHasKey('stock_quantity', $result);
        $this->assertArrayNotHasKey('description', $result);
    }

    public function testCreateCustomerDtoFromArray(): void
    {
        $data = [
            'name' => 'João Silva',
            'email' => 'joao@exemplo.com',
            'cpf' => '123.456.789-01',
            'phone' => '(11) 98888-7777',
        ];

        $dto = CreateCustomerDTO::fromArray($data);

        $this->assertEquals('João Silva', $dto->name);
        $this->assertEquals('joao@exemplo.com', $dto->email);
        $this->assertEquals('12345678901', $dto->cpf);
        $this->assertEquals('11988887777', $dto->phone);
    }

    public function testCreateCustomerDtoCpfCleaning(): void
    {
        $testCases = [
            '123.456.789-01' => '12345678901',
            '12345678901' => '12345678901',
            '123 456 789 01' => '12345678901',
            'abc123def456ghi789jkl01mno' => '12345678901',
        ];

        foreach ($testCases as $input => $expected) {
            $cleaned = CreateCustomerDTO::cleanCpf($input);
            $this->assertEquals($expected, $cleaned, "Failed for input: {$input}");
        }
    }

    public function testUpdateCustomerDtoPartialUpdate(): void
    {
        $data = [
            'email' => 'novoemail@exemplo.com',
            'phone' => '(11) 97777-6666',
        ];

        $dto = UpdateCustomerDTO::fromArray($data);

        $this->assertNull($dto->name);
        $this->assertEquals('novoemail@exemplo.com', $dto->email);
        $this->assertNull($dto->cpf);
        $this->assertEquals('11977776666', $dto->phone);
        $this->assertTrue($dto->hasUpdates());
    }

    public function testCreateCustomerDtoPhoneCleaning(): void
    {
        $testCases = [
            '(11) 99999-9999' => '11999999999',
            '1133334444' => '1133334444',
            '+55 (11) 98888-7777' => '5511988887777',
        ];

        foreach ($testCases as $input => $expected) {
            $cleaned = CreateCustomerDTO::cleanPhone($input);
            $this->assertEquals($expected, $cleaned, "Failed for input: {$input}");
        }
    }

    public function testDtoJsonSerialization(): void
    {
        $data = [
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 19.99,
            'stock_quantity' => 10,
        ];

        $dto = CreateProductDTO::fromArray($data);
        $json = $dto->toJson();

        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertEquals($data['name'], $decoded['name']);
        $this->assertEquals($data['price'], $decoded['price']);
    }

    public function testDtoOnlyMethod(): void
    {
        $data = [
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 19.99,
            'stock_quantity' => 10,
        ];

        $dto = CreateProductDTO::fromArray($data);
        $result = $dto->only(['name', 'price']);

        $this->assertEquals([
            'name' => 'Test Product',
            'price' => 19.99,
        ], $result);
    }

    public function testDtoExceptMethod(): void
    {
        $data = [
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 19.99,
            'stock_quantity' => 10,
        ];

        $dto = CreateProductDTO::fromArray($data);
        $result = $dto->except(['description', 'image']);

        $this->assertEquals([
            'name' => 'Test Product',
            'price' => 19.99,
            'stock_quantity' => 10,
        ], $result);
    }
}

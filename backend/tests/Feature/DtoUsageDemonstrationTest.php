<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DataTransferObjects\Customer\CreateCustomerDTO;
use App\DataTransferObjects\Product\CreateProductDTO;
use App\DataTransferObjects\Product\UpdateProductDTO;
use Tests\TestCase;

class DtoUsageDemonstrationTest extends TestCase
{
    public function testCreateProductFromFormData(): void
    {
        $formData = [
            'name' => 'MacBook Pro 16"',
            'description' => 'MacBook Pro com chip M3 Max, 32GB RAM, SSD 1TB',
            'price' => 12999.90,
            'stock_quantity' => 5,
        ];

        $productDto = CreateProductDTO::fromArray($formData);

        $this->assertIsString($productDto->name);
        $this->assertIsFloat($productDto->price);
        $this->assertIsInt($productDto->stockQuantity);
        $this->assertNull($productDto->image);

        $this->assertEquals('MacBook Pro 16"', $productDto->name);
        $this->assertEquals(12999.90, $productDto->price);
        $this->assertEquals(5, $productDto->stockQuantity);

        $arrayData = $productDto->toArray();
        $this->assertEquals($formData['name'], $arrayData['name']);
        $this->assertEquals($formData['stock_quantity'], $arrayData['stock_quantity']);
    }

    public function testPartialProductUpdate(): void
    {
        $updateData = [
            'name' => 'MacBook Pro 16" - Promoção',
            'price' => 11499.90,
        ];

        $updateDto = UpdateProductDTO::fromArray($updateData);

        $this->assertEquals('MacBook Pro 16" - Promoção', $updateDto->name);
        $this->assertEquals(11499.90, $updateDto->price);
        $this->assertNull($updateDto->stockQuantity);
        $this->assertNull($updateDto->description);

        $this->assertTrue($updateDto->hasUpdates());

        $updateArray = $updateDto->toArray();
        $this->assertArrayHasKey('name', $updateArray);
        $this->assertArrayHasKey('price', $updateArray);
        $this->assertArrayNotHasKey('stock_quantity', $updateArray);
        $this->assertArrayNotHasKey('description', $updateArray);

        $this->assertEquals([
            'name' => 'MacBook Pro 16" - Promoção',
            'price' => 11499.90,
        ], $updateArray);
    }

    public function testCustomerWithCpfCleaning(): void
    {
        $customerData = [
            'name' => 'Maria Silva Santos',
            'email' => 'maria.silva@empresa.com.br',
            'cpf' => '123.456.789-01', // CPF formatado
        ];

        $customerDto = CreateCustomerDTO::fromArray($customerData);

        $this->assertEquals('12345678901', $customerDto->cpf);
        $this->assertEquals('Maria Silva Santos', $customerDto->name);
        $this->assertEquals('maria.silva@empresa.com.br', $customerDto->email);

        $dirtyInputs = [
            '123.456.789-01' => '12345678901',
            '123 456 789 01' => '12345678901',
            'abc123.456.789-01def' => '12345678901',
            '12345678901' => '12345678901', // Já limpo
        ];

        foreach ($dirtyInputs as $input => $expected) {
            $this->assertEquals($expected, CreateCustomerDTO::cleanCpf($input));
        }
    }

    /**
     * @throws \JsonException
     */
    public function testBaseDtoUtilities(): void
    {
        $productData = [
            'name' => 'iPhone 15 Pro',
            'description' => 'iPhone 15 Pro com câmera profissional',
            'price' => 8999.00,
            'stock_quantity' => 12,
        ];

        $dto = CreateProductDTO::fromArray($productData);

        $json = $dto->toJson();
        $this->assertJson($json);
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('iPhone 15 Pro', $decoded['name']);

        $nameAndPrice = $dto->only(['name', 'price']);
        $this->assertEquals([
            'name' => 'iPhone 15 Pro',
            'price' => 8999.00,
        ], $nameAndPrice);

        $withoutDescription = $dto->except(['description', 'image']);
        $this->assertEquals([
            'name' => 'iPhone 15 Pro',
            'price' => 8999.00,
            'stock_quantity' => 12,
        ], $withoutDescription);
    }

    public function testRealisticControllerUsage(): void
    {
        $createData = [
            'name' => 'Samsung Galaxy S24',
            'description' => 'Smartphone flagship com IA avançada',
            'price' => 4299.99,
            'stock_quantity' => 25,
        ];

        $createDto = CreateProductDTO::fromArray($createData);
        $this->assertEquals($createData, $createDto->toArray());

        $updateData = [
            'price' => 3999.99,
        ];

        $updateDto = UpdateProductDTO::fromArray($updateData);

        if ($updateDto->hasUpdates()) {
            $updateArray = $updateDto->toArray();
            $this->assertEquals(['price' => 3999.99], $updateArray);
        }

        $customerData = [
            'name' => 'João da Silva',
            'email' => 'joao@email.com',
            'cpf' => '987.654.321-00',
        ];

        $customerDto = CreateCustomerDTO::fromArray($customerData);

        $expectedArray = [
            'name' => 'João da Silva',
            'email' => 'joao@email.com',
            'cpf' => '98765432100', // CPF limpo
        ];

        $this->assertEquals($expectedArray, $customerDto->toArray());
    }

    public function testTypeSafetyDemonstration(): void
    {
        $data = [
            'name' => 'Test Product',
            'price' => '123.45',
            'stock_quantity' => '10',
        ];

        $dto = CreateProductDTO::fromArray($data);

        // ✅ Tipos foram convertidos corretamente
        $this->assertIsString($dto->name);
        $this->assertIsFloat($dto->price);
        $this->assertIsInt($dto->stockQuantity);

        $this->assertEquals(123.45, $dto->price);
        $this->assertEquals(10, $dto->stockQuantity);
    }

    public function testComparisonWithArrayApproach(): void
    {
        $data = [
            'name' => 'Comparison Test',
            'price' => 99.99,
            'stock_quantity' => 1,
        ];

        $dto = CreateProductDTO::fromArray($data);

        $price = $dto->price;
        $name = $dto->name;
        $stock = $dto->stockQuantity;

        $backToArray = $dto->toArray();
        $this->assertEquals($data, $backToArray);

        $this->assertIsFloat($price);
        $this->assertIsString($name);
        $this->assertIsInt($stock);
    }

    public function testEdgeCasesAndNullHandling(): void
    {
        $minimalProduct = [
            'name' => 'Minimal Product',
            'price' => 10.00,
            'stock_quantity' => 1,
        ];

        $dto = CreateProductDTO::fromArray($minimalProduct);

        $this->assertEquals('Minimal Product', $dto->name);
        $this->assertNull($dto->description);
        $this->assertNull($dto->image);

        $emptyUpdate = [];
        $updateDto = UpdateProductDTO::fromArray($emptyUpdate);

        $this->assertFalse($updateDto->hasUpdates());
        $this->assertEquals([], $updateDto->toArray());

        $customerWithCleanCpf = [
            'name' => 'Test User',
            'email' => 'test@test.com',
            'cpf' => '12345678901',
        ];

        $customerDto = CreateCustomerDTO::fromArray($customerWithCleanCpf);
        $this->assertEquals('12345678901', $customerDto->cpf);
    }
}

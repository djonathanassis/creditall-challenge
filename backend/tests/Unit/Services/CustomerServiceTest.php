<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Contracts\CustomerRepositoryInterface;
use App\DataTransferObjects\Customer\CreateCustomerDTO;
use App\DataTransferObjects\Customer\UpdateCustomerDTO;
use App\Exceptions\CustomerException;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class CustomerServiceTest extends TestCase
{
    private CustomerService $service;
    private MockInterface $customerRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customerRepository = Mockery::mock(CustomerRepositoryInterface::class);
        $this->service = new CustomerService($this->customerRepository);
    }

    public function testCreateCustomer(): void
    {
        $dto = new CreateCustomerDTO(
            name: 'John Doe',
            email: 'john@example.com',
            cpf: '12345678901',
            phone: '11999999999'
        );

        $customer = new Customer(['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'phone' => '11999999999']);

        $this->customerRepository
            ->allows('create')
            ->with($dto->toArray())
            ->andReturns($customer);

        $result = $this->service->createCustomer($dto);

        $this->assertEquals('John Doe', $result->name);
        $this->assertEquals('john@example.com', $result->email);
    }

    public function testUpdateCustomer(): void
    {
        $this->markTestSkipped('Requires database for fresh() method');
    }

    public function testUpdateCustomerThrowsWhenNoUpdates(): void
    {
        $customer = new Customer(['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']);
        $dto = new UpdateCustomerDTO();

        $this->expectException(CustomerException::class);
        $this->expectExceptionMessage('Nenhuma atualização foi fornecida para o cliente');

        $this->service->updateCustomer($customer, $dto);
    }

    public function testDeleteCustomer(): void
    {
        $customer = new Customer(['id' => 1, 'name' => 'John Doe']);

        $this->customerRepository
            ->allows('hasAssociatedSales')
            ->with($customer)
            ->andReturns(false);

        $this->customerRepository
            ->allows('delete')
            ->with($customer)
            ->andReturns(true);

        $result = $this->service->deleteCustomer($customer);

        $this->assertTrue($result);
    }

    public function testDeleteCustomerThrowsWhenHasSales(): void
    {
        $customer = new Customer(['id' => 1, 'name' => 'John Doe']);

        $this->customerRepository
            ->allows('hasAssociatedSales')
            ->with($customer)
            ->andReturns(true);

        $this->expectException(CustomerException::class);
        $this->expectExceptionMessage('Não é possível excluir cliente com vendas associadas');

        $this->service->deleteCustomer($customer);
    }

    public function testGetCustomerWithStats(): void
    {
        $customer = new Customer(['id' => 1, 'name' => 'John Doe']);

        $this->customerRepository
            ->allows('findWithSalesStats')
            ->with(1)
            ->andReturns($customer);

        $result = $this->service->getCustomerWithStats(1);

        $this->assertEquals('John Doe', $result->name);
    }

    public function testGetCustomerWithStatsReturnsNull(): void
    {
        $this->customerRepository
            ->allows('findWithSalesStats')
            ->with(999)
            ->andReturns(null);

        $result = $this->service->getCustomerWithStats(999);

        $this->assertNull($result);
    }

    public function testGetCustomerSales(): void
    {
        $customer = new Customer(['id' => 1, 'name' => 'John Doe']);
        $request = new Request();
        $paginator = new LengthAwarePaginator([], 0, 15);

        $this->customerRepository
            ->allows('getSalesWithPagination')
            ->with($customer, $request)
            ->andReturns($paginator);

        $result = $this->service->getCustomerSales($customer, $request);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    public function testCanDeleteReturnsTrueWhenNoSales(): void
    {
        $customer = new Customer(['id' => 1, 'name' => 'John Doe']);

        $this->customerRepository
            ->allows('hasAssociatedSales')
            ->with($customer)
            ->andReturns(false);

        $result = $this->service->canDelete($customer);

        $this->assertTrue($result);
    }

    public function testCanDeleteReturnsFalseWhenHasSales(): void
    {
        $customer = new Customer(['id' => 1, 'name' => 'John Doe']);

        $this->customerRepository
            ->allows('hasAssociatedSales')
            ->with($customer)
            ->andReturns(true);

        $result = $this->service->canDelete($customer);

        $this->assertFalse($result);
    }

    public function testFindCustomerOrFail(): void
    {
        $customer = new Customer(['id' => 1, 'name' => 'John Doe']);

        $this->customerRepository
            ->allows('find')
            ->with(1)
            ->andReturns($customer);

        $result = $this->service->findCustomerOrFail(1);

        $this->assertEquals('John Doe', $result->name);
    }

    public function testFindCustomerOrFailThrowsWhenNotFound(): void
    {
        $this->customerRepository
            ->allows('find')
            ->with(999)
            ->andReturns(null);

        $this->expectException(CustomerException::class);
        $this->expectExceptionMessage('Cliente não encontrado');

        $this->service->findCustomerOrFail(999);
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\CustomerRepositoryInterface;
use App\DataTransferObjects\Customer\CreateCustomerDTO;
use App\DataTransferObjects\Customer\UpdateCustomerDTO;
use App\Exceptions\CustomerException;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

readonly class CustomerService
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository
    ) {}

    /**
     * @throws CustomerException
     * @throws \Throwable
     */
    public function createCustomer(CreateCustomerDTO $dto): Customer
    {
        return DB::transaction(function () use ($dto) {
            return $this->customerRepository->create($dto->toArray());
        });
    }

    /**
     * @throws CustomerException
     * @throws \Throwable
     */
    public function updateCustomer(Customer $customer, UpdateCustomerDTO $dto): Customer
    {
        if (! $dto->hasUpdates()) {
            throw new CustomerException('Nenhuma atualização foi fornecida para o cliente');
        }

        return DB::transaction(function () use ($customer, $dto) {
            $this->customerRepository->update($customer, $dto->toArray());

            return $customer->fresh();
        });
    }

    /**
     * @throws CustomerException
     * @throws \Throwable
     */
    public function deleteCustomer(Customer $customer): bool
    {
        if ($this->customerRepository->hasAssociatedSales($customer)) {
            throw new CustomerException('Não é possível excluir cliente com vendas associadas');
        }

        return DB::transaction(function () use ($customer) {
            return $this->customerRepository->delete($customer);
        });
    }

    public function getCustomerWithStats(int $customerId): ?Customer
    {
        return $this->customerRepository->findWithSalesStats($customerId);
    }

    public function getCustomerSales(Customer $customer, Request $request): LengthAwarePaginator
    {
        return $this->customerRepository->getSalesWithPagination($customer, $request);
    }

    public function canDelete(Customer $customer): bool
    {
        return ! $this->customerRepository->hasAssociatedSales($customer);
    }

    /**
     * @throws CustomerException
     */
    public function findCustomerOrFail(int $customerId): Model
    {
        $customer = $this->customerRepository->find($customerId);

        if (! $customer) {
            throw new CustomerException('Cliente não encontrado');
        }

        return $customer;
    }
}

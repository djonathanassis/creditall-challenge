<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | API Response Messages
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default API response messages
    | used throughout the application. These messages are returned in JSON
    | responses to provide consistent feedback to API consumers.
    |
    */

    'success' => [
        // Authentication
        'login' => 'Login realizado com sucesso',
        'logout' => 'Logout realizado com sucesso',

        // Products
        'product_created' => 'Produto criado com sucesso',
        'product_updated' => 'Produto atualizado com sucesso',
        'product_deleted' => 'Produto excluído com sucesso',

        // Customers
        'customer_created' => 'Cliente criado com sucesso',
        'customer_updated' => 'Cliente atualizado com sucesso',
        'customer_deleted' => 'Cliente excluído com sucesso',

        // Sales
        'sale_created' => 'Venda criada com sucesso',
        'sale_updated' => 'Venda atualizada com sucesso',
        'sale_deleted' => 'Venda excluída com sucesso',
        'sale_status_updated' => 'Status da venda atualizado com sucesso',
    ],

    'errors' => [
        // Authentication
        'invalid_credentials' => 'As credenciais fornecidas estão incorretas.',
        'unauthorized' => 'Não autorizado.',
        'forbidden' => 'Acesso negado.',

        // General
        'resource_not_found' => 'Recurso não encontrado',
        'server_error' => 'Erro interno do servidor',
        'validation_failed' => 'Erro na validação dos dados',

        // Business Logic
        'product_has_sales' => 'Não é possível excluir produto que foi vendido',
        'customer_has_sales' => 'Não é possível excluir cliente que possui vendas',
        'no_valid_fields' => 'Nenhum campo válido para atualizar',

        // Operations
        'sale_creation_failed' => 'Falha ao criar venda: :details',
        'sale_update_failed' => 'Falha ao atualizar status da venda: :details',

        // Stock/Business Rules
        'insufficient_stock' => 'Estoque insuficiente para :product. Solicitado: :requested, Disponível: :available',
        'invalid_status_transition' => 'Não é possível alterar status da venda de :from para :to',
    ],
];

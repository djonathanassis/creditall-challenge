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
        'login' => 'Successfully logged in',
        'logout' => 'Successfully logged out',

        // Products
        'product_created' => 'Product created successfully',
        'product_updated' => 'Product updated successfully',
        'product_deleted' => 'Product deleted successfully',

        // Customers
        'customer_created' => 'Customer created successfully',
        'customer_updated' => 'Customer updated successfully',
        'customer_deleted' => 'Customer deleted successfully',

        // Sales
        'sale_created' => 'Sale created successfully',
        'sale_updated' => 'Sale updated successfully',
        'sale_deleted' => 'Sale deleted successfully',
        'sale_status_updated' => 'Sale status updated successfully',
    ],

    'errors' => [
        // Authentication
        'invalid_credentials' => 'The provided credentials are incorrect.',
        'unauthorized' => 'Unauthorized.',
        'forbidden' => 'Access denied.',

        // General
        'resource_not_found' => 'Resource not found',
        'server_error' => 'Internal server error',
        'validation_failed' => 'Validation error',

        // Business Logic
        'product_has_sales' => 'Cannot delete product that has been sold',
        'customer_has_sales' => 'Cannot delete customer with existing sales',
        'no_valid_fields' => 'No valid fields to update',

        // Operations
        'sale_creation_failed' => 'Failed to create sale: :details',
        'sale_update_failed' => 'Failed to update sale status: :details',

        // Stock/Business Rules
        'insufficient_stock' => 'Insufficient stock for :product. Requested: :requested, Available: :available',
        'invalid_status_transition' => 'Cannot change sale status from :from to :to',
    ],
];

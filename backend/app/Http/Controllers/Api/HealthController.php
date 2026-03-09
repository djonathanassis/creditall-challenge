<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * @group 🛠️ Utilitários
 *
 * Endpoints utilitários para monitoramento e verificação do sistema.
 * Inclui health check e informações de status da aplicação.
 */
class HealthController extends Controller
{
    /**
     * Health Check
     *
     * Endpoint público para verificação de saúde da API.
     * Retorna informações sobre o status da aplicação, timestamp e versão.
     *
     * Este endpoint é usado para:
     * - Monitoramento de uptime
     * - Load balancer health checks
     * - Verificação de conectividade
     * - Deploy validation
     *
     * @unauthenticated
     *
     * @response 200 scenario="API funcionando corretamente" {
     *   "status": "healthy",
     *   "timestamp": "2026-03-06T13:45:00.012870Z",
     *   "version": "1.0.0"
     * }
     */
    public function check(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'version' => config('app.version', '1.0.0'),
        ]);
    }
}

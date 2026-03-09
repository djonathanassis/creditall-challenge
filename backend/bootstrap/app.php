<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Excluir rotas API da validação CSRF
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);

        // Configure unauthenticated responses for API routes
        $middleware->redirectGuestsTo(function (Request $request) {
            // Para rotas API, SEMPRE retornar JSON (independente do Accept header)
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não autorizado. Token de autenticação necessário.',
                ], 401);
            }

            // Para rotas web, redirecionar para login
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não autorizado. Token de autenticação necessário.',
                ], 401);
            }

            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Para todas as exceções HTTP em rotas API, retornar JSON
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                // MethodNotAllowedHttpException
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Método não permitido',
                        'errors' => ['method' => ['O método ' . $request->method() . ' não é permitido para esta rota']]
                    ], 405);
                }

                // NotFoundHttpException (404)
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Rota não encontrada',
                        'errors' => ['route' => ['A rota solicitada não existe']]
                    ], 404);
                }

                // Para outros erros, retornar JSON
                if ($e->getCode() >= 400) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Erro na requisição',
                        'errors' => ['server' => [$e->getMessage()]]
                    ], $e->getCode() ?: 500);
                }
            }
        });
    })->create();

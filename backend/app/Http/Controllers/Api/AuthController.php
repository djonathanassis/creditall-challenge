<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * @group 🔐 Autenticação
 *
 * Endpoints para gerenciar autenticação de usuários na API.
 * Utiliza Laravel Sanctum para autenticação baseada em tokens Bearer.
 */
class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    /**
     * Login de usuário
     *
     * Autentica um usuário com email e senha, retornando as informações do usuário e um token de acesso.
     *
     * @unauthenticated
     *
     * @bodyParam email string required O email do usuário. Deve ser um email válido. Example: usuario@exemplo.com
     * @bodyParam password string required A senha do usuário. Mínimo 8 caracteres. Example: minhasenha123
     *
     * @response 200 scenario="Login bem-sucedido" {
     *   "Success": true,
     *   "message": "Login realizado com sucesso",
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "João Silva",
     *       "email": "joao@exemplo.com",
     *       "email_verified_at": "2026-03-06T10:30:00.000000Z",
     *       "created_at": "2026-03-06T10:30:00.000000Z",
     *       "updated_at": "2026-03-06T10:30:00.000000Z"
     *     },
     *     "token": "1|AbCdEfGhIjKlMnOpQrStUvWxYz1234567890abcdef"
     *   }
     * }
     * @response 422 scenario="Credenciais inválidas" {
     *   "success": false,
     *   "message": "Erro na validação dos dados",
     *   "errors": {
     *     "email": ["As credenciais fornecidas são inválidas."]
     *   }
     * }
     * @response 422 scenario="Dados de validação inválidos" {
     *   "success": false,
     *   "message": "Erro na validação dos dados",
     *   "errors": {
     *     "email": ["O campo email é obrigatório."],
     *     "password": ["O campo senha é obrigatório."]
     *   }
     * }
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $result = $this->authService->login($request->email, $request->password);

        if (!$result) {
            throw ValidationException::withMessages([
                'email' => [__('api.errors.invalid_credentials')],
            ]);
        }

        return $this->successWithDataResponse(
            [
                'user' => $result['user'],
                'token' => $result['token'],
            ],
            'api.success.login'
        );
    }

    /**
     * Logout de usuário
     *
     * Revoga o token de acesso atual do usuário autenticado, efetuando logout da sessão.
     *
     * @authenticated
     *
     * @response 200 scenario="Logout bem-sucedido" {
     *   "Success": true,
     *   "message": "Logout realizado com sucesso",
     *   "data": null
     * }
     * @response 401 scenario="Token inválido ou expirado" {
     *   "success": false,
     *   "message": "Token de acesso inválido ou expirado"
     * }
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->successResponse(null, 'api.success.logout');
    }

    /**
     * Informações do usuário autenticado
     *
     * Retorna as informações completas do usuário atualmente autenticado.
     *
     * @authenticated
     *
     * @response 200 scenario="Usuário autenticado" {
     *   "Success": true,
     *   "message": "Dados recuperados com sucesso",
     *   "data": {
     *     "id": 1,
     *     "name": "João Silva",
     *     "email": "joao@exemplo.com",
     *     "email_verified_at": "2026-03-06T10:30:00.000000Z",
     *     "created_at": "2026-03-06T10:30:00.000000Z",
     *     "updated_at": "2026-03-06T10:30:00.000000Z"
     *   }
     * }
     * @response 401 scenario="Token inválido ou expirado" {
     *   "success": false,
     *   "message": "Token de acesso inválido ou expirado"
     * }
     */
    public function me(Request $request): JsonResponse
    {
        return $this->successResponse($request->user());
    }
}

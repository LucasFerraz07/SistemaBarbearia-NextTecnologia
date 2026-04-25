<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    public function __construct(private AuthService $authService) {}

    #[OA\Post(
        path: '/api/register',
        summary: 'Cadastro de cliente',
        tags: ['Autenticação'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'phone', 'cep', 'number'],
                properties: [
                    new OA\Property(property: 'name',     type: 'string'),
                    new OA\Property(property: 'email',    type: 'string'),
                    new OA\Property(property: 'password', type: 'string'),
                    new OA\Property(property: 'phone',    type: 'string'),
                    new OA\Property(property: 'cep',      type: 'string'),
                    new OA\Property(property: 'number',   type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Cliente cadastrado com sucesso'),
            new OA\Response(response: 422, description: 'Erro de validação'),
        ]
    )]
    public function register(RegisterRequest $request)
    {
        $user = $this->authService->register($request->validated());

        return response()->json($user, 201);
    }

    #[OA\Post(
        path: '/api/login',
        summary: 'Login de usuário',
        tags: ['Autenticação'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email',    type: 'string'),
                    new OA\Property(property: 'password', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Retorna o token de acesso'),
            new OA\Response(response: 401, description: 'Credenciais inválidas'),
        ]
    )]
    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        if (!Auth::attempt($validated)) {
            return response()->json(['message' => 'Credenciais inválidas.'], 401);
        }

        $user = User::where('email', $validated['email'])->first();
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['token' => $token], 200);
    }

    #[OA\Post(
        path: '/api/logout',
        summary: 'Logout de usuário',
        tags: ['Autenticação'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Logout realizado com sucesso'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout realizado com sucesso.'], 200);
    }
}

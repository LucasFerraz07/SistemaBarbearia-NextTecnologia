<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    #[OA\Post(
        path: '/api/register',
        summary: 'Cadastro de cliente',
        tags: ['Autenticação'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'phone', 'address', 'city'],
                properties: [
                    new OA\Property(property: 'name',     type: 'string'),
                    new OA\Property(property: 'email',    type: 'string'),
                    new OA\Property(property: 'password', type: 'string'),
                    new OA\Property(property: 'phone',    type: 'string'),
                    new OA\Property(property: 'address',  type: 'string'),
                    new OA\Property(property: 'city',     type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Cliente cadastrado com sucesso'),
            new OA\Response(response: 422, description: 'Erro de validação'),
        ]
    )]
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone'    => 'required|string|max:20',
            'address'  => 'required|string|max:255',
            'city'     => 'required|string|max:100',
        ]);

        $clientType = UserType::where('name', 'cliente')->firstOrFail();

        $user = User::create([
            'name'         => $validated['name'],
            'email'        => $validated['email'],
            'password'     => $validated['password'],
            'user_type_id' => $clientType->id,
        ]);

        Client::create([
            'user_id' => $user->id,
            'phone'   => $validated['phone'],
            'address' => $validated['address'],
            'city'    => $validated['city'],
        ]);

        return response()->json($user->load('client'), 201);
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
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ]);

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
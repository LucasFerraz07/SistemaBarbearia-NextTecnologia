<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AdminController extends Controller
{
    #[OA\Get(
        path: '/api/admins',
        summary: 'Listar administradores',
        security: [['bearerAuth' => []]],
        tags: ['Administradores'],
        responses: [
            new OA\Response(response: 200, description: 'Lista de administradores'),
            new OA\Response(response: 403, description: 'Acesso não autorizado'),
        ]
    )]
    public function index()
    {
        $adminType = UserType::where('name', 'administrador')->firstOrFail();

        $admins = User::where('user_type_id', $adminType->id)
            ->with('userType')
            ->get();

        return response()->json($admins, 200);
    }

    #[OA\Post(
        path: '/api/admins',
        summary: 'Cadastrar administrador',
        security: [['bearerAuth' => []]],
        tags: ['Administradores'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'email', type: 'string'),
                    new OA\Property(property: 'password', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Administrador cadastrado com sucesso'),
            new OA\Response(response: 403, description: 'Acesso não autorizado'),
            new OA\Response(response: 422, description: 'Erro de validação'),
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $adminType = UserType::where('name', 'administrador')->firstOrFail();

        $admin = User::create([
            'name'         => $validated['name'],
            'email'        => $validated['email'],
            'password'     => $validated['password'],
            'user_type_id' => $adminType->id,
        ]);

        return response()->json($admin, 201);
    }

    #[OA\Get(
        path: '/api/admins/{id}',
        summary: 'Buscar administrador',
        security: [['bearerAuth' => []]],
        tags: ['Administradores'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Dados do administrador'),
            new OA\Response(response: 403, description: 'Acesso não autorizado'),
            new OA\Response(response: 404, description: 'Administrador não encontrado'),
        ]
    )]
    public function show($id)
    {
        $adminType = UserType::where('name', 'administrador')->firstOrFail();

        $admin = User::where('id', $id)
            ->where('user_type_id', $adminType->id)
            ->first();

        if (!$admin) {
            return response()->json(['message' => 'Administrador não encontrado'], 404);
        }

        return response()->json($admin, 200);
    }

    #[OA\Put(
        path: '/api/admins/{id}',
        summary: 'Atualizar administrador',
        security: [['bearerAuth' => []]],
        tags: ['Administradores'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'email', type: 'string'),
                    new OA\Property(property: 'password', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Administrador atualizado com sucesso'),
            new OA\Response(response: 403, description: 'Acesso não autorizado'),
            new OA\Response(response: 404, description: 'Administrador não encontrado'),
        ]
    )]
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'email'    => 'sometimes|string|email|max:255|unique:users,email,' . $id,
            'password' => 'sometimes|string|min:8',
        ]);

        $adminType = UserType::where('name', 'administrador')->firstOrFail();

        $admin = User::where('id', $id)
            ->where('user_type_id', $adminType->id)
            ->first();

        if (!$admin) {
            return response()->json(['message' => 'Administrador não encontrado'], 404);
        }

        $admin->update($validated);

        return response()->json($admin, 200);
    }

    #[OA\Delete(
        path: '/api/admins/{id}',
        summary: 'Remover administrador',
        security: [['bearerAuth' => []]],
        tags: ['Administradores'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Administrador removido com sucesso'),
            new OA\Response(response: 403, description: 'Acesso não autorizado'),
            new OA\Response(response: 404, description: 'Administrador não encontrado'),
        ]
    )]
    public function destroy($id)
    {
        $adminType = UserType::where('name', 'administrador')->firstOrFail();

        $admin = User::where('id', $id)
            ->where('user_type_id', $adminType->id)
            ->first();

        if (!$admin) {
            return response()->json(['message' => 'Administrador não encontrado'], 404);
        }

        $admin->delete();

        return response()->json(['message' => 'Administrador removido com sucesso'], 200);
    }
}

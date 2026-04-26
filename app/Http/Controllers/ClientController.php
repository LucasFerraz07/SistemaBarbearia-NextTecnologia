<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Models\Client;
use App\Services\ClientService;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ClientController extends Controller
{
    public function __construct(private ClientService $clientService) {}

    #[OA\Get(
        path: '/api/clients',
        summary: 'Listar clientes',
        description: 'Apenas administradores podem listar todos os clientes',
        security: [['bearerAuth' => []]],
        tags: ['Clientes'],
        responses: [
            new OA\Response(response: 200, description: 'Lista de clientes'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function index(Request $request)
    {
        if ($request->user()?->userType?->name !== 'administrador') {
            return response()->json(['message' => 'Acesso não autorizado'], 403);
        }

        return response()->json($this->clientService->index(), 200);
    }

    #[OA\Get(
        path: '/api/clients/{id}',
        summary: 'Buscar cliente',
        description: 'Administradores podem ver qualquer cliente. Clientes podem ver apenas os próprios dados',
        security: [['bearerAuth' => []]],
        tags: ['Clientes'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Dados do cliente'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Cliente não encontrado'),
        ]
    )]
    public function show(Request $request, $id)
    {
        $client = $this->clientService->show((int) $id);

        if (!$client) {
            return response()->json(['message' => 'Cliente não encontrado'], 404);
        }

        if (!$this->isAdminOrOwner($request, $client)) {
            return response()->json(['message' => 'Acesso não autorizado'], 403);
        }

        return response()->json($client, 200);
    }

    #[OA\Patch(
        path: '/api/clients/{id}',
        summary: 'Atualizar cliente',
        description: 'Administradores podem atualizar qualquer cliente. Clientes podem atualizar apenas os próprios dados',
        security: [['bearerAuth' => []]],
        tags: ['Clientes'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name',   type: 'string', example: 'João Silva Atualizado'),
                    new OA\Property(property: 'email',  type: 'string', example: 'joaoatualizado@email.com'),
                    new OA\Property(property: 'phone',  type: 'string', example: '12988888888'),
                    new OA\Property(property: 'cep',    type: 'string', example: '01310100'),
                    new OA\Property(property: 'number', type: 'string', example: '100'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Cliente atualizado com sucesso'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Cliente não encontrado'),
            new OA\Response(response: 422, description: 'CEP não encontrado ou erro de validação'),
        ]
    )]
    public function update(UpdateClientRequest $request, $id)
    {
        $client = $this->clientService->show((int) $id);

        if (!$client) {
            return response()->json(['message' => 'Cliente não encontrado'], 404);
        }

        if (!$this->isAdminOrOwner($request, $client)) {
            return response()->json(['message' => 'Acesso não autorizado'], 403);
        }

        $updated = $this->clientService->update((int) $id, $request->validated());

        return response()->json($updated, 200);
    }

    #[OA\Delete(
        path: '/api/clients/{id}',
        summary: 'Remover cliente',
        description: 'Administradores podem remover qualquer cliente. Clientes podem remover apenas a própria conta',
        security: [['bearerAuth' => []]],
        tags: ['Clientes'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Cliente removido com sucesso'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Cliente não encontrado'),
        ]
    )]
    public function destroy(Request $request, $id)
    {
        $client = $this->clientService->show((int) $id);

        if (!$client) {
            return response()->json(['message' => 'Cliente não encontrado'], 404);
        }

        if (!$this->isAdminOrOwner($request, $client)) {
            return response()->json(['message' => 'Acesso não autorizado'], 403);
        }

        $this->clientService->destroy((int) $id);

        return response()->json(['message' => 'Cliente removido com sucesso'], 200);
    }

    private function isAdminOrOwner(Request $request, Client $client): bool
    {
        $isAdmin = $request->user()?->userType?->name === 'administrador';
        $isOwner = $client->user_id === $request->user()->id;

        return $isAdmin || $isOwner;
    }
}

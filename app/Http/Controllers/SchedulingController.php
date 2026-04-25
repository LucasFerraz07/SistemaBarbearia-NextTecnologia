<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Scheduling\StoreSchedulingRequest;
use App\Http\Requests\Scheduling\UpdateSchedulingRequest;
use App\Models\Scheduling;
use App\Services\SchedulingService;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SchedulingController extends Controller
{
    public function __construct(private SchedulingService $schedulingService) {}

    #[OA\Get(
        path: '/api/schedulings',
        summary: 'Listar agendamentos',
        description: 'Qualquer usuário autenticado pode listar os agendamentos. Retorna id, start_date e end_date',
        security: [['bearerAuth' => []]],
        tags: ['Agendamentos'],
        responses: [
            new OA\Response(response: 200, description: 'Lista de agendamentos'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function index(Request $request)
    {
        $filters = $request->only(['client_id', 'start_date', 'end_date']);

        $isClient = $request->user()?->userType?->name === 'cliente';

        if ($isClient && $request->has('client_id')) {
            $filters['client_id'] = $request->user()->client->id;
        }

        return response()->json($this->schedulingService->index($filters), 200);
    }

    #[OA\Get(
        path: '/api/schedulings/{id}',
        summary: 'Buscar agendamento',
        description: 'Administradores podem ver qualquer agendamento. Clientes podem ver apenas os próprios agendamentos',
        security: [['bearerAuth' => []]],
        tags: ['Agendamentos'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Dados do agendamento'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 403, description: 'Acesso não autorizado'),
            new OA\Response(response: 404, description: 'Agendamento não encontrado'),
        ]
    )]
    public function show(Request $request, $id)
    {
        $scheduling = $this->schedulingService->show((int) $id);

        if (!$scheduling) {
            return response()->json(['message' => 'Agendamento não encontrado'], 404);
        }

        if (!$this->isAdminOrOwner($request, $scheduling)) {
            return response()->json(['message' => 'Acesso não autorizado'], 403);
        }

        return response()->json($scheduling, 200);
    }

    #[OA\Post(
        path: '/api/schedulings',
        summary: 'Criar agendamento',
        description: 'Qualquer usuário autenticado pode criar um agendamento. Administradores são notificados por e-mail',
        security: [['bearerAuth' => []]],
        tags: ['Agendamentos'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['client_id', 'start_date'],
                properties: [
                    new OA\Property(property: 'client_id',  type: 'integer'),
                    new OA\Property(property: 'start_date', type: 'string', format: 'date-time'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Agendamento criado com sucesso'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 422, description: 'Erro de validação'),
        ]
    )]
    public function store(StoreSchedulingRequest $request)
    {
        $isClient = $request->user()?->userType?->name === 'cliente';
        $validated = $request->validated();

        if ($isClient) {
            $client = $request->user()->client;

            if (!$client) {
                return response()->json(['message' => 'Perfil de cliente não encontrado'], 404);
            }

            $validated['client_id'] = $client->id;
        }

        $scheduling = $this->schedulingService->store($validated);

        return response()->json($scheduling, 201);
    }

    #[OA\Put(
        path: '/api/schedulings/{id}',
        summary: 'Atualizar agendamento',
        description: 'Administradores podem atualizar qualquer agendamento. Clientes podem atualizar apenas os próprios agendamentos',
        security: [['bearerAuth' => []]],
        tags: ['Agendamentos'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'start_date', type: 'string', format: 'date-time'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Agendamento atualizado com sucesso'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 403, description: 'Acesso não autorizado'),
            new OA\Response(response: 404, description: 'Agendamento não encontrado'),
            new OA\Response(response: 422, description: 'Erro de validação'),
        ]
    )]
    public function update(UpdateSchedulingRequest $request, $id)
    {
        $scheduling = $this->schedulingService->show((int) $id);

        if (!$scheduling) {
            return response()->json(['message' => 'Agendamento não encontrado'], 404);
        }

        if (!$this->isAdminOrOwner($request, $scheduling)) {
            return response()->json(['message' => 'Acesso não autorizado'], 403);
        }

        $updated = $this->schedulingService->update((int) $id, $request->validated());

        return response()->json($updated, 200);
    }

    #[OA\Delete(
        path: '/api/schedulings/{id}',
        summary: 'Remover agendamento',
        description: 'Administradores podem remover qualquer agendamento. Clientes podem remover apenas os próprios agendamentos',
        security: [['bearerAuth' => []]],
        tags: ['Agendamentos'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Agendamento removido com sucesso'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 403, description: 'Acesso não autorizado'),
            new OA\Response(response: 404, description: 'Agendamento não encontrado'),
        ]
    )]
    public function destroy(Request $request, $id)
    {
        $scheduling = $this->schedulingService->show((int) $id);

        if (!$scheduling) {
            return response()->json(['message' => 'Agendamento não encontrado'], 404);
        }

        if (!$this->isAdminOrOwner($request, $scheduling)) {
            return response()->json(['message' => 'Acesso não autorizado'], 403);
        }

        $this->schedulingService->destroy((int) $id);

        return response()->json(['message' => 'Agendamento removido com sucesso'], 200);
    }

    private function isAdminOrOwner(Request $request, Scheduling $scheduling): bool
    {
        $isAdmin = $request->user()?->userType?->name === 'administrador';
        $isOwner = $scheduling->client?->user_id === $request->user()->id;

        return $isAdmin || $isOwner;
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\NewSchedulingNotification;
use App\Models\Scheduling;
use App\Models\User;
use App\Models\UserType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use OpenApi\Attributes as OA;

class SchedulingController extends Controller
{
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
    public function index()
    {
        $schedulings = Scheduling::select('id', 'start_date', 'end_date')->get();

        return response()->json($schedulings, 200);
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
        $scheduling = Scheduling::with('client.user')->find($id);

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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id'  => 'required|integer|exists:clients,id',
            'start_date' => 'required|date',
        ]);

        $endDate = Carbon::parse($validated['start_date'])->addMinutes(90);

        $scheduling = DB::transaction(function () use ($validated, $endDate) {
            $conflict = Scheduling::lockForUpdate()
                ->where('start_date', '<', $endDate)
                ->where('end_date', '>', $validated['start_date'])
                ->exists();

            if ($conflict) {
                abort(422, 'Horário já está reservado');
            }

            return Scheduling::create([
                'client_id'  => $validated['client_id'],
                'start_date' => $validated['start_date'],
                'end_date'   => $endDate,
            ]);
        });

        $scheduling->load('client.user');

        $adminType = UserType::where('name', 'administrador')->firstOrFail();
        $admins = User::where('user_type_id', $adminType->id)->get();

        foreach ($admins as $admin) {
            Mail::to($admin->email)->send(new NewSchedulingNotification($scheduling));
        }

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
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'start_date' => 'sometimes|date',
        ]);

        $scheduling = Scheduling::find($id);

        if (!$scheduling) {
            return response()->json(['message' => 'Agendamento não encontrado'], 404);
        }

        if (!$this->isAdminOrOwner($request, $scheduling)) {
            return response()->json(['message' => 'Acesso não autorizado'], 403);
        }

        DB::transaction(function () use ($validated, $scheduling, $id) {
            if (isset($validated['start_date'])) {
                $endDate = Carbon::parse($validated['start_date'])->addMinutes(90);

                $conflict = Scheduling::lockForUpdate()
                    ->where('id', '!=', $id)
                    ->where('start_date', '<', $endDate)
                    ->where('end_date', '>', $validated['start_date'])
                    ->exists();

                if ($conflict) {
                    abort(422, 'Horário já está reservado');
                }

                $scheduling->update([
                    'start_date' => $validated['start_date'],
                    'end_date'   => $endDate,
                ]);
            }
        });

        return response()->json($scheduling->fresh(), 200);
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
        $scheduling = Scheduling::find($id);

        if (!$scheduling) {
            return response()->json(['message' => 'Agendamento não encontrado'], 404);
        }

        if (!$this->isAdminOrOwner($request, $scheduling)) {
            return response()->json(['message' => 'Acesso não autorizado'], 403);
        }

        $scheduling->delete();

        return response()->json(['message' => 'Agendamento removido com sucesso'], 200);
    }

    private function isAdminOrOwner(Request $request, Scheduling $scheduling): bool
    {
        $isAdmin = $request->user()?->userType?->name === 'administrador';
        $isOwner = $scheduling->client?->user_id === $request->user()->id;

        return $isAdmin || $isOwner;
    }
}

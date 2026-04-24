<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use OpenApi\Attributes as OA;

class ClientController extends Controller
{
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

        $clients = Client::with('user', 'address.city')->get();

        return response()->json($clients, 200);
    }

    #[OA\Get(
        path: '/api/clients/{id}',
        summary: 'Buscar cliente',
        description: 'Administradores podem ver qualquer cliente. Clientes podem ver apenas os próprios dados',
        security: [['bearerAuth' => []]],
        tags: ['Clientes'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Dados do cliente'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Cliente não encontrado'),
        ]
    )]
    public function show(Request $request, $id)
    {
        $client = Client::with('user', 'address.city')->find($id);

        if (!$client) {
            return response()->json(['message' => 'Cliente não encontrado'], 404);
        }

        if (!$this->isAdminOrOwner($request, $client)) {
            return response()->json(['message' => 'Acesso não autorizado'], 403);
        }

        return response()->json($client, 200);
    }

    #[OA\Put(
        path: '/api/clients/{id}',
        summary: 'Atualizar cliente',
        description: 'Administradores podem atualizar qualquer cliente. Clientes podem atualizar apenas os próprios dados',
        security: [['bearerAuth' => []]],
        tags: ['Clientes'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name',   type: 'string'),
                    new OA\Property(property: 'email',  type: 'string'),
                    new OA\Property(property: 'phone',  type: 'string'),
                    new OA\Property(property: 'cep',    type: 'string'),
                    new OA\Property(property: 'number', type: 'string'),
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
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name'   => 'sometimes|string|max:255',
            'email'  => 'sometimes|string|email|max:255|unique:users,email,' . $id,
            'phone'  => 'sometimes|string|max:20',
            'cep'    => 'sometimes|string|max:9',
            'number' => 'sometimes|string|max:20',
        ]);

        $client = Client::find($id);

        if (!$client) {
            return response()->json(['message' => 'Cliente não encontrado'], 404);
        }

        if (!$this->isAdminOrOwner($request, $client)) {
            return response()->json(['message' => 'Acesso não autorizado'], 403);
        }

        if (isset($validated['cep']) || isset($validated['number'])) {
            $cep = $validated['cep'] ?? $client->address->cep;
            $number = $validated['number'] ?? $client->address->number;

            $viaCep = Http::get("https://viacep.com.br/ws/{$cep}/json/");

            if ($viaCep->failed() || isset($viaCep->json()['erro'])) {
                return response()->json(['message' => 'CEP não encontrado'], 422);
            }

            $cepData = $viaCep->json();
            $city = City::firstOrCreate(['name' => $cepData['localidade']]);

            $client->address->update([
                'city_id'      => $city->id,
                'street'       => $cepData['logradouro'],
                'number'       => $number,
                'neighborhood' => $cepData['bairro'],
                'cep'          => $cep,
            ]);
        }

        if (isset($validated['name']) || isset($validated['email'])) {
            $client->user->update(array_filter([
                'name'  => $validated['name']  ?? null,
                'email' => $validated['email'] ?? null,
            ]));
        }

        if (isset($validated['phone'])) {
            $client->update(['phone' => $validated['phone']]);
        }

        return response()->json($client->load('user', 'address.city'), 200);
    }

    #[OA\Delete(
        path: '/api/clients/{id}',
        summary: 'Remover cliente',
        description: 'Administradores podem remover qualquer cliente. Clientes podem remover apenas a própria conta',
        security: [['bearerAuth' => []]],
        tags: ['Clientes'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Cliente removido com sucesso'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Cliente não encontrado'),
        ]
    )]
    public function destroy(Request $request, $id)
    {
        $client = Client::find($id);

        if (!$client) {
            return response()->json(['message' => 'Cliente não encontrado'], 404);
        }

        if (!$this->isAdminOrOwner($request, $client)) {
            return response()->json(['message' => 'Acesso não autorizado'], 403);
        }

        $client->delete();

        return response()->json(['message' => 'Cliente removido com sucesso'], 200);
    }

    private function isAdminOrOwner(Request $request, Client $client): bool
    {
        $isAdmin = $request->user()?->userType?->name === 'administrador';
        $isOwner = $client->user_id === $request->user()->id;

        return $isAdmin || $isOwner;
    }
}

<?php

namespace App\Services;

use App\Models\City;
use App\Models\Client;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;

class ClientService
{
    public function index(): Collection
    {
        return Client::with('user', 'address.city')->get();
    }

    public function show(int $id): ?Client
    {
        return Client::with('user', 'address.city')->find($id);
    }

    public function update(int $id, array $data): ?Client
    {
        $client = Client::find($id);

        if (!$client) {
            return null;
        }

        if (isset($data['cep']) || isset($data['number'])) {
            $cep    = $data['cep']    ?? $client->address->cep;
            $number = $data['number'] ?? $client->address->number;

            $viaCep = Http::get("https://viacep.com.br/ws/{$cep}/json/");

            if ($viaCep->failed() || isset($viaCep->json()['erro'])) {
                abort(422, 'CEP não encontrado');
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

        if (isset($data['name']) || isset($data['email'])) {
            $client->user->update(array_filter([
                'name'  => $data['name']  ?? null,
                'email' => $data['email'] ?? null,
            ]));
        }

        if (isset($data['phone'])) {
            $client->update(['phone' => $data['phone']]);
        }

        return $client->load('user', 'address.city');
    }

    public function destroy(int $id): bool
    {
        $client = Client::find($id);

        if (!$client) {
            return false;
        }

        $client->delete();

        return true;
    }
}

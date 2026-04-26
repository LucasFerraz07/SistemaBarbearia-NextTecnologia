<?php

namespace App\Services;

use App\Models\Address;
use App\Models\City;
use App\Models\Client;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AuthService
{
    public function register(array $data): User
    {
        $viaCep = Http::get("https://viacep.com.br/ws/{$data['cep']}/json/");

        if ($viaCep->failed() || isset($viaCep->json()['erro'])) {
            abort(422, 'CEP não encontrado');
        }

        $cepData = $viaCep->json();

        $clientType = UserType::where('name', 'cliente')->firstOrFail();

        $user = DB::transaction(function () use ($data, $cepData, $clientType) {
            $user = User::create([
                'name'         => $data['name'],
                'email'        => $data['email'],
                'password'     => $data['password'],
                'user_type_id' => $clientType->id,
            ]);

            $city = City::firstOrCreate(['name' => $cepData['localidade']]);

            $address = Address::create([
                'city_id'      => $city->id,
                'street'       => $cepData['logradouro'],
                'number'       => $data['number'],
                'neighborhood' => $cepData['bairro'],
                'cep'          => $data['cep'],
            ]);

            Client::create([
                'user_id'    => $user->id,
                'phone'      => $data['phone'],
                'address_id' => $address->id,
            ]);

            return $user;
        });

        return $user->load('client.address.city');
    }
}

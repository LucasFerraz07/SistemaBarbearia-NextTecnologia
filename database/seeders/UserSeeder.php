<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminTypeId = UserType::where('name', 'administrador')->value('id');

        User::create([
            'name'         => 'Administrador',
            'email'        => 'admin@barbearia.com',
            'password'     => Hash::make('password'),
            'user_type_id' => $adminTypeId,
        ]);
    }
}

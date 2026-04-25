<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserType;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminService
{
    public function index(): LengthAwarePaginator
    {
        $adminType = UserType::where('name', 'administrador')->firstOrFail();

        return User::where('user_type_id', $adminType->id)
            ->with('userType')
            ->paginate(10);
    }

    public function store(array $data): User
    {
        $adminType = UserType::where('name', 'administrador')->firstOrFail();

        return User::create([
            'name'         => $data['name'],
            'email'        => $data['email'],
            'password'     => $data['password'],
            'user_type_id' => $adminType->id,
        ]);
    }

    public function show(int $id): ?User
    {
        $adminType = UserType::where('name', 'administrador')->firstOrFail();

        return User::where('id', $id)
            ->where('user_type_id', $adminType->id)
            ->first();
    }

    public function update(int $id, array $data): ?User
    {
        $admin = $this->show($id);

        if (!$admin) {
            return null;
        }

        $admin->update($data);

        return $admin;
    }

    public function destroy(int $id): bool
    {
        $admin = $this->show($id);

        if (!$admin) {
            return false;
        }

        $admin->delete();

        return true;
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
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
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['token' => $token], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout realizado com sucesso.'], 200);
    }
}
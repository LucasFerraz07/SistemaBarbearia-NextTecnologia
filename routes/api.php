<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\SchedulingController;
use Illuminate\Support\Facades\Route;

// Rotas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rotas autenticadas
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Clientes
    Route::apiResource('clients', ClientController::class)->except(['store']);

    // Agendamentos
    Route::apiResource('schedulings', SchedulingController::class);

    // Administradores
    Route::middleware('admin')->group(function () {
        Route::apiResource('admins', AdminController::class);
    });
});
<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompetitionController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\PlayerController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::middleware(['auth:api', 'throttle:api'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Datos de fútbol
    Route::get('/competitions', [CompetitionController::class, 'index'])->middleware('can:competitions.read');
    Route::get('/competitions/{competition_id}', [CompetitionController::class, 'show'])
        ->middleware('can:competitions.import')
        ->where('competition_id', '[A-Za-z0-9]+');
    Route::get('/team', [TeamController::class, 'index'])->middleware('can:teams.read');
    Route::get('/team/{team_id}', [TeamController::class, 'show'])->middleware('can:teams.read')->whereNumber('team_id');
    Route::get('/players', [PlayerController::class, 'index'])->middleware('can:players.read');

    // Administración
    Route::apiResource('users', UserController::class)->except('destroy')->middleware('can:users.manage');
    Route::get('/roles', [RoleController::class, 'index'])->middleware('can:roles.list');
    Route::apiResource('roles', RoleController::class)->except(['index', 'show'])->middleware('can:roles.manage');
    Route::get('/permissions', [PermissionController::class, 'index'])->middleware('can:roles.manage');
});

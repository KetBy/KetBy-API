<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Authentication routes
Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function ($router) {
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/register', [AuthController::class, 'register'])->middleware('recaptcha');
    Route::post('/confirm', [AuthController::class, 'confirm']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/status', [AuthController::class, 'status']);    
});

// Project routes
Route::group([
    'middleware' => 'api',
    'prefix' => 'project'
], function ($router) {
    // Route::get('/', [ProjectController::class, 'index'])->middleware('auth');
    Route::post('/', [ProjectController::class, 'create'])->middleware('auth');
    Route::get('/{token}', [ProjectController::class, 'getProject']);
    Route::put('/{token}/{fileIndex}', [ProjectController::class, 'updateFile']);
});

// User routes
Route::group([
    'middleware' => 'api',
    'prefix' => 'user'
], function ($router) {
    Route::get('/{username}', [UserController::class, 'index']);
    Route::get('/{username}/projects', [UserController::class, 'getProjects']);
});
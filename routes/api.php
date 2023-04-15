<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PageController;

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

// Page routes
Route::group([
    'middleware' => 'api',
    'prefix' => 'page'
], function ($router) {
    Route::get("/index", [PageController::class, 'index']);
});

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
    Route::post('/', [ProjectController::class, 'create'])->middleware('auth'); // create a new project
    Route::get('/{token}', [ProjectController::class, 'getProject']); // get project data
    Route::post('/{token}', [ProjectController::class, 'createFile']); // create a new file
    Route::put('/{token}/{fileIndex}', [ProjectController::class, 'updateFile']); // update a file's content
    Route::post('/{token}/{fileIndex}', [ProjectController::class, 'updateFileSettings']); // update a file (e.g. title)
    Route::delete('/{token}/{fileIndex}', [ProjectController::class, 'deleteFile']); // delete a file
    Route::get('/{token}/{fileIndex}/stats', [ProjectController::class, 'getStats']); // get a circuit statistics 
});

// User routes
Route::group([
    'middleware' => 'api',
    'prefix' => 'user'
], function ($router) {
    Route::get('/{username}', [UserController::class, 'index']);
    Route::get('/{username}/projects', [UserController::class, 'getProjects']);
});
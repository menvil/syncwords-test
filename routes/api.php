<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\EventController;
use App\Http\Controllers\API\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/', function() {
    return response()->setStatusCode(404);
});

Route::controller(AuthController::class)->group(function(){
    Route::post('login', 'login');
});

Route::middleware('auth:sanctum')->group( function () {
    Route::get('list', [EventController::class, 'index']);
    Route::get('{id}', [EventController::class, 'show']);
    Route::post('', [EventController::class, 'store']);
    Route::put('{id}', [EventController::class, 'update']);
    Route::patch('{id}', [EventController::class, 'update']);
    Route::delete('{id}', [EventController::class, 'destroy']);
});

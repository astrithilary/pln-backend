<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PelangganController;
use App\Http\Controllers\Api\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Authentication routes (public)
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (require auth)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::get('/user/profile', [AuthController::class, 'getUser']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Endpoint Sinkronisasi untuk Flutter
    Route::post('/sync-pelanggan', [PelangganController::class, 'store']);
    Route::post('/upload-foto/{id}', [PelangganController::class, 'uploadFoto']);
Route::get('/foto/{path}', [PelangganController::class, 'showFoto'])->where('path', '.*');

// Endpoint untuk Dashboard Website
Route::get('/pelanggans', [PelangganController::class, 'index']);
Route::delete('/pelanggans/{id}', [PelangganController::class, 'destroy']);

});
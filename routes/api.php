<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PelangganController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/user/profile', [AuthController::class, 'getUser']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/sync-pelanggan', [PelangganController::class, 'store']);
    Route::post('/upload-foto/{id}', [PelangganController::class, 'uploadFoto']);
});

Route::get('/foto/{path}', [PelangganController::class, 'showFoto'])->where('path', '.*');
Route::get('/pelanggans', [PelangganController::class, 'index']);
Route::delete('/pelanggans/{id}', [PelangganController::class, 'destroy']);

<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PelangganController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Endpoint Sinkronisasi untuk Flutter
Route::post('/sync-pelanggan', [PelangganController::class, 'store']);
Route::post('/upload-foto/{id}', [PelangganController::class, 'uploadFoto']);

// Endpoint untuk Dashboard Website
Route::get('/pelanggans', [PelangganController::class, 'index']);
Route::delete('/pelanggans/{id}', [PelangganController::class, 'destroy']);
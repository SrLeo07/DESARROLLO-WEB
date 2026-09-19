<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ProductController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->name('api.login');

Route::middleware('auth:api')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::get('/me', [AuthController::class, 'me'])->name('api.me');

    Route::middleware('scope:productos.read')->group(function (): void {
        Route::get('/productos', [ProductController::class, 'index'])->name('productos.index');
        Route::get('/productos/{product}', [ProductController::class, 'show'])->name('productos.show');
    });

    Route::middleware('scope:productos.write')->group(function (): void {
        Route::post('/productos', [ProductController::class, 'store'])->name('productos.store');
        Route::match(['put', 'patch'], '/productos/{product}', [ProductController::class, 'update'])
            ->name('productos.update');
    });

    Route::delete('/productos/{product}', [ProductController::class, 'destroy'])
        ->middleware('scope:productos.delete')
        ->name('productos.destroy');
});

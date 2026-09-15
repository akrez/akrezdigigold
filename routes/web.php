<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);
Route::prefix(config('app.admin_route_prefix'))->group(function () {
    Route::get('/shell', [HomeController::class, 'shell']);
    Route::post('/run', [HomeController::class, 'run'])->name('home.run');
});
<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShellController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);
Route::group([], function () {
    Route::get('/', [ShellController::class, 'index'])->name('index');
    Route::post('/', [ShellController::class, 'store'])->name('store');
})->prefix('shell')->name('shell');
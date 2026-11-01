<?php

use App\Http\Controllers\AiController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShellController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);

Route::group(['prefix' => 'shell', 'as' => 'shell.'], function () {
    Route::get('/', [ShellController::class, 'index'])->name('index');
    Route::post('/', [ShellController::class, 'store'])->name('store');
});

Route::group(['prefix' => 'ai', 'as' => 'ai.'], function () {
    Route::get('/', [AiController::class, 'index'])->name('index');
    Route::post('/', [AiController::class, 'store'])->name('store');
});

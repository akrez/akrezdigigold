<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShellController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);
Route::resource('shell', ShellController::class)->only(['create', 'store']);

<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShellController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);
Route::resource('admin361', ShellController::class)->only(['create', 'store'])->names('shell');

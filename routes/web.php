<?php

use App\Http\Controllers\PasswordController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PasswordController::class, 'showForm'])->name('passwords.form');
Route::post('/generate', [PasswordController::class, 'generate'])->name('passwords.generate');

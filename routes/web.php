<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/register', [RegisterController::class, 'create'])->name('register');
Route::post('/register', [RegisterController::class, 'store']);
Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store']);
Route::get('/otp', [OtpController::class, 'show'])->name('otp.show');
Route::post('/otp', [OtpController::class, 'verify'])->name('otp.verify');
Route::post('/otp/resend', [OtpController::class, 'resend'])->name('otp.resend');
Route::get('/lupa-password', [PasswordResetController::class, 'create'])->name('password.request');
Route::post('/lupa-password', [PasswordResetController::class, 'store'])->name('password.email');
Route::get('/lupa-password/otp', [PasswordResetController::class, 'otp'])->name('password.otp');
Route::post('/lupa-password/otp', [PasswordResetController::class, 'verify'])->name('password.otp.verify');
Route::post('/lupa-password/otp/resend', [PasswordResetController::class, 'resend'])->name('password.otp.resend');
Route::get('/password-baru', [PasswordResetController::class, 'edit'])->name('password.reset');
Route::post('/password-baru', [PasswordResetController::class, 'update'])->name('password.update');
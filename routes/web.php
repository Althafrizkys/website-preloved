<?php

use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NegotiationController;
use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Halaman untuk yang belum login. Yang sudah login dialihkan keluar dari sini.
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    Route::get('/admin/login', [AdminLoginController::class, 'create'])->name('admin.login');
    Route::post('/admin/login', [AdminLoginController::class, 'store']);

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
});

// Halaman khusus admin. Route dashboard admin ditaruh di dalam grup ini.
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Sementara, akan diganti oleh dashboard admin.
    Route::get('/', fn () => 'Dashboard admin (sementara)')->name('dashboard');
});

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('home');
})->middleware('auth')->name('logout');

Route::get('/barang/{product}', [ProductController::class, 'show'])
    ->whereNumber('product')
    ->name('products.show');

Route::middleware('auth')->group(function () {
    Route::get('/jual', [ProductController::class, 'create'])->name('products.create');
    Route::post('/jual', [ProductController::class, 'store'])->name('products.store');
    Route::get('/barang/{product}/edit', [ProductController::class, 'edit'])
        ->whereNumber('product')
        ->name('products.edit');
    Route::put('/barang/{product}', [ProductController::class, 'update'])
        ->whereNumber('product')
        ->name('products.update');
    Route::patch('/barang/{product}/status', [ProductController::class, 'markSold'])
        ->whereNumber('product')
        ->name('products.status');
    Route::delete('/barang/{product}', [ProductController::class, 'destroy'])
        ->whereNumber('product')
        ->name('products.destroy');
    Route::get('/barang/{product}/nego', [NegotiationController::class, 'show'])
        ->whereNumber('product')
        ->name('negotiations.show');
    Route::post('/barang/{product}/nego', [NegotiationController::class, 'store'])
        ->whereNumber('product')
        ->name('negotiations.store');
    Route::post('/nego/{negotiation}/terima', [NegotiationController::class, 'accept'])
        ->whereNumber('negotiation')
        ->name('negotiations.accept');
    Route::post('/nego/{negotiation}/tolak', [NegotiationController::class, 'reject'])
        ->whereNumber('negotiation')
        ->name('negotiations.reject');
});

<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NegotiationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RewardController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WishlistDealController;
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
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // CRUD Titik COD & Kampus
    Route::post('/meeting-points', [AdminDashboardController::class, 'storeMeetingPoint'])->name('meeting-points.store');
    Route::put('/meeting-points/{meetingPoint}', [AdminDashboardController::class, 'updateMeetingPoint'])
        ->whereNumber('meetingPoint')
        ->name('meeting-points.update');
    Route::delete('/meeting-points/{meetingPoint}', [AdminDashboardController::class, 'destroyMeetingPoint'])
        ->whereNumber('meetingPoint')
        ->name('meeting-points.destroy');
    Route::delete('/products/{product}', [AdminDashboardController::class, 'destroyProduct'])
    ->name('products.destroy');

    // Manajemen Akun & Role User
    Route::patch('/users/{user}/toggle-status', [AdminDashboardController::class, 'toggleUserStatus'])
        ->whereNumber('user')
        ->name('users.toggle-status');
    Route::patch('/users/{user}/role', [AdminDashboardController::class, 'updateUserRole'])
        ->whereNumber('user')
        ->name('users.role');
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

    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifikasi/{notification}/baca', [NotificationController::class, 'markAsRead'])
        ->whereNumber('notification')
        ->name('notifications.read');
    Route::post('/notifikasi/baca-semua', [NotificationController::class, 'markAllAsRead'])
        ->name('notifications.readAll');

    // Chat
    Route::post('/chat/mulai/{product}', [ChatController::class, 'start'])
        ->whereNumber('product')
        ->name('chat.start');
    Route::post('/chat/wishlist/{wishlist}/mulai', [ChatController::class, 'startWishlist'])
        ->whereNumber('wishlist')
        ->name('chat.wishlist.start');
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/{conversation}', [ChatController::class, 'show'])
        ->whereNumber('conversation')
        ->name('chat.show');
    Route::post('/chat/{conversation}/wishlist-offers', [WishlistDealController::class, 'store'])
        ->whereNumber('conversation')
        ->name('wishlist-deals.store');
    Route::post('/wishlist-offers/{negotiation}/terima', [WishlistDealController::class, 'accept'])
        ->whereNumber('negotiation')
        ->name('wishlist-deals.accept');
    Route::post('/wishlist-offers/{negotiation}/tolak', [WishlistDealController::class, 'reject'])
        ->whereNumber('negotiation')
        ->name('wishlist-deals.reject');
    Route::get('/chat/{conversation}/pesan', [ChatController::class, 'messages'])
        ->whereNumber('conversation')
        ->name('chat.messages');
    Route::post('/chat/{conversation}/pesan', [ChatController::class, 'store'])
        ->whereNumber('conversation')
        ->name('chat.store');

    // Transaksi & COD
    Route::get('/transaksi', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transaksi/export', [TransactionController::class, 'export'])->name('transactions.export');
    Route::get('/transaksi/buat/{product}', [TransactionController::class, 'create'])
        ->whereNumber('product')
        ->name('transactions.create');
    Route::get('/transaksi/buat-wishlist/{negotiation}', [TransactionController::class, 'createWishlist'])
        ->whereNumber('negotiation')
        ->name('transactions.create-wishlist');
    Route::post('/transaksi', [TransactionController::class, 'store'])->name('transactions.store');
    Route::get('/transaksi/{transaction}', [TransactionController::class, 'show'])
        ->whereNumber('transaction')
        ->name('transactions.show');
    Route::get('/transaksi/{transaction}/chat', [TransactionController::class, 'chat'])
        ->whereNumber('transaction')
        ->name('transactions.chat');
    Route::post('/transaksi/{transaction}/rating', [TransactionController::class, 'storeRating'])
        ->whereNumber('transaction')
        ->name('transactions.rating');
    Route::post('/transaksi/{transaction}/konfirmasi', [TransactionController::class, 'confirm'])
        ->whereNumber('transaction')
        ->name('transactions.confirm');
    Route::post('/transaksi/{transaction}/selesai', [TransactionController::class, 'complete'])
        ->whereNumber('transaction')
        ->name('transactions.complete');
    Route::post('/transaksi/{transaction}/batal', [TransactionController::class, 'cancel'])
        ->whereNumber('transaction')
        ->name('transactions.cancel');
    Route::get('/rewards', [RewardController::class, 'index'])->name('rewards.index');

    // ===== Profil toko & wishlist (Akmal) =====
Route::get('/toko/{user}', [\App\Http\Controllers\ProfileController::class, 'show'])
    ->whereNumber('user')
    ->name('store.show');

    Route::get('/wishlist', [\App\Http\Controllers\WishlistController::class, 'index'])->name('wishlist.index');


Route::middleware('auth')->group(function () {
    Route::get('/profil-saya', [\App\Http\Controllers\ProfileController::class, 'me'])->name('profile.me');
    Route::get('/profil-saya/edit', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil-saya', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');

    Route::get('/wishlist/buat', [\App\Http\Controllers\WishlistController::class, 'create'])->name('wishlist.create');
    Route::post('/wishlist', [\App\Http\Controllers\WishlistController::class, 'store'])->name('wishlist.store');
    Route::get('/wishlist/{wishlist}/edit', [\App\Http\Controllers\WishlistController::class, 'edit'])
        ->whereNumber('wishlist')
        ->name('wishlist.edit');
    Route::put('/wishlist/{wishlist}', [\App\Http\Controllers\WishlistController::class, 'update'])
        ->whereNumber('wishlist')
        ->name('wishlist.update');
    Route::delete('/wishlist/{wishlist}', [\App\Http\Controllers\WishlistController::class, 'destroy'])
        ->whereNumber('wishlist')
        ->name('wishlist.destroy');
});
});
<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);

        // Alur jual meminta pendaftaran; admin dan route auth lain tetap memakai redirect masing-masing.
        $middleware->redirectGuestsTo(fn (Request $request) => match (true) {
            $request->is('admin', 'admin/*') => route('admin.login'),
            $request->is('jual', 'jual/*', 'barang/*/edit')
                || (
                    $request->is('barang/*')
                    && in_array($request->method(), ['PUT', 'PATCH', 'DELETE'], true)
                ) => route('register'),
            default => route('login'),
        });

        // Yang sudah login tidak perlu membuka halaman tamu lagi.
        $middleware->redirectUsersTo(fn (Request $request) => $request->user()?->isAdmin()
            ? route('admin.dashboard')
            : '/');
    })
    ->withExceptions(function (Exceptions $exceptions): void {})->create();

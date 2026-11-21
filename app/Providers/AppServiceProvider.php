<?php

namespace App\Providers;

use App\Models\UserNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(['components.navbar', 'notifications._modal', 'notifications.index'], function ($view) {
            if (Auth::check()) {
                $user = Auth::user();
                $notifications = $user->notifications()
                    ->with([
                        'product' => fn ($query) => $query->with([
                            'media' => fn ($mediaQuery) => $mediaQuery->where('media_type', 'foto')->orderBy('display_order'),
                        ]),
                        'negotiation',
                    ])
                    ->orderByDesc('created_at')
                    ->orderByDesc('notification_id')
                    ->get();

                $view->with([
                    'notifications' => $notifications,
                    'unreadNotificationCount' => $notifications->where('is_read', false)->count(),
                ]);
            } else {
                $view->with([
                    'notifications' => collect(),
                    'unreadNotificationCount' => 0,
                ]);
            }
        });
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();

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

        if ($request->ajax() && ! $request->hasHeader('X-Requested-Full-Page')) {
            return response()->json([
                'notifications' => $notifications,
                'unread_count' => $notifications->where('is_read', false)->count(),
            ]);
        }

        return view('notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $notifications->where('is_read', false)->count(),
        ]);
    }

    public function markAsRead(Request $request, UserNotification $notification): JsonResponse|RedirectResponse
    {
        if ((int) $notification->user_id !== (int) $request->user()->user_id) {
            abort(403, 'Akses tidak diizinkan.');
        }

        if (! $notification->is_read) {
            $notification->update(['is_read' => true]);
        }

        $unreadCount = $request->user()->notifications()->where('is_read', false)->count();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'unread_count' => $unreadCount,
            ]);
        }

        return back();
    }

    public function markAllAsRead(Request $request): JsonResponse|RedirectResponse
    {
        $query = $request->user()->notifications()->where('is_read', false);

        if ($request->filled('role')) {
            $query->where('role', $request->query('role'));
        }

        $query->update(['is_read' => true]);

        $unreadCount = $request->user()->notifications()->where('is_read', false)->count();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'unread_count' => $unreadCount,
            ]);
        }

        return back();
    }
}

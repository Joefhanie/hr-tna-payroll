<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class NotificationController extends Controller
{
    public function show(Request $request, string $notification): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);
        abort_unless(
            Schema::hasTable('notifications')
                && Schema::hasColumn('notifications', 'notifiable_type')
                && Schema::hasColumn('notifications', 'notifiable_id'),
            503,
            'Notifications are unavailable until the latest migration is run.'
        );

        $notificationModel = $user->notifications()->where('id', $notification)->firstOrFail();

        if ($notificationModel->read_at === null) {
            $notificationModel->markAsRead();
        }

        $targetUrl = (string) data_get($notificationModel->data, 'url', route('dashboard'));

        return redirect()->to($targetUrl);
    }

    public function markAllRead(): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);
        abort_unless(
            Schema::hasTable('notifications')
                && Schema::hasColumn('notifications', 'notifiable_type')
                && Schema::hasColumn('notifications', 'notifiable_id'),
            503,
            'Notifications are unavailable until the latest migration is run.'
        );

        $user->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'Notifications marked as read.');
    }
}

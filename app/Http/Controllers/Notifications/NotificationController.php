<?php

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function open(
        Request $request,
        string $notification
    ): RedirectResponse {
        $user =
            $request->user();

        $notificationRecord =
            $user
                ->notifications()
                ->where(
                    'id',
                    $notification
                )
                ->firstOrFail();


        if (
            is_null(
                $notificationRecord->read_at
            )
        ) {
            $notificationRecord
                ->markAsRead();
        }


        $url =
            $notificationRecord
                ->data[
                    'url'
                ]
            ??
            route('dashboard');


        return redirect(
            $url
        );
    }


    public function markAllRead(
        Request $request
    ): RedirectResponse {
        $request
            ->user()
            ->unreadNotifications
            ->markAsRead();


        return back()
            ->with(
                'success',
                'Notifications marked as read.'
            );
    }
}
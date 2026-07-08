<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationWebController extends Controller
{
    public function markRead(DatabaseNotification $notification)
    {
        abort_if($notification->notifiable_id !== auth()->id(), 403);

        $notification->markAsRead();

        return redirect()->back();
    }

    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return redirect()->back();
    }
}
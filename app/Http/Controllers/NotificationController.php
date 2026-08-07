<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $notifications = $user->notifications()->paginate(20);

        // Mark everything read once the user opens the list.
        $user->unreadNotifications->markAsRead();

        return view('notifications.index', compact('notifications'));
    }
}

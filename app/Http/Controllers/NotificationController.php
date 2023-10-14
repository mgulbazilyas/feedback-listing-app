<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    //
    public function index()
    {
        $notifications = Notification::all();
        return response()->json(['notifications' => $notifications], 200);
    }

}

<?php

namespace App\Listeners;

use App\Events\FeedbackCreatedOrUpdated;
use App\Events\NotificationCreatedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Notification;
class FeedbackCreatedListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(FeedbackCreatedOrUpdated $event): void
    {
        //
        $notificationData = [
            'title' => 'Feedback Created',
            'subtitle' => 'A new feedback has been created!',
            'link' => '/feedback/' . $event->feedback->id, // Adjust the link as needed
        ];
        $notification = new Notification($notificationData);
        $notification->save();
        event(new NotificationCreatedEvent($notification));
    }
}

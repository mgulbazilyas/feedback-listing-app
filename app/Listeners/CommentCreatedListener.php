<?php

namespace App\Listeners;

use App\Events\CommentCreated;
use App\Events\NotificationCreatedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CommentCreatedListener
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
    public function handle(CommentCreated $event): void
    {
        //
        // event(new NotificationCreatedEvent())
    }
}

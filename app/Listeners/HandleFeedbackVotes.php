<?php

namespace App\Listeners;

use App\Events\VoteEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandleFeedbackVotes
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
    public function handle(VoteEvent $event): void
    {
        $feedback = $event->feedback;
        $feedback->upvotes = $feedback->upvotes + $event->upvotes;
        $feedback->downvotes = $feedback->downvotes + $event->downvotes;
        $feedback->timestamps = false;
        $feedback->save();
        //
    }
}


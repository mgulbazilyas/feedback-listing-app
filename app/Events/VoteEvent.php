<?php

namespace App\Events;

use App\Models\Feedback;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VoteEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $feedback;
    public $upvotes;
    public $downvotes;
    /**
     * Create a new event instance.
     */
    public function __construct(Feedback $feedback, int $upvoteCount, $downvoteCount)
    {
        $this->feedback = $feedback;
        $this->upvotes = $upvoteCount;
        $this->downvotes = $downvoteCount;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('votes'),
        ];
    }
    public function broadcastAs(): string
    {
        return 'voted';
    }
}

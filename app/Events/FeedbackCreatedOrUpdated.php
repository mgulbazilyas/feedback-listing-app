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

class FeedbackCreatedOrUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $feedback;
    public $created;

    /**
     * Create a new event instance.
     */
    public function __construct(Feedback $feedback, $created=false)
    {
        $this->feedback = $feedback;
        $this->created = $created;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('app'),
        ];
    }
    public function broadcastAs(): string
    {
        return 'feedbackCreateOrUpdate';
    }
    
}

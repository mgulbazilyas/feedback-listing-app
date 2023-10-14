<?php

namespace App\Events;

use App\Models\Comment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    protected $comment;
    protected $created;
    /**
     * Create a new event instance.
     */
    public function __construct(Comment $comment, $created=true)
    {
        $this->comment = $comment;
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
        return 'comment-create-or-update';
    }
}

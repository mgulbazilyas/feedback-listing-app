<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VoteController extends Controller
{
    
    public function vote(Request $request, $id, $type)
    {
        $feedback = Feedback::findOrFail($id);
        $user = $request->user('web');
        // Check if the user has already voted
        $existingVote = Vote::where('user_id', $user->id)
                            ->where('feedback_id', $feedback->id)
                            ->first();
    
        $upvote_count = 0;
        $downvote_count = 0;
        $type = ($type == 'upvote') ? true : false;
        // dd($existingVote);
        if ($existingVote!=null) {
            // check if vote matches 
            if($type==$existingVote->type){
                return response()->json(['message' => 'Same vote cannot be voted.'], 400); 
            }
            if ($existingVote->type) {
                // Existing vote is an upvote, change to downvote
                $existingVote->type = false;
                $downvote_count = 1;
                $upvote_count = -1;
                
            } else {
                // Existing vote is a downvote, change to upvote
                $existingVote->type = true;
                $downvote_count = -1;
                $upvote_count = 1;
            }
            $existingVote->save();
        } else {
            // Create a new vote record
            // $type = ($type == 'upvote') ? true : false;
    
            Vote::create([
                'type' => $type,
                'feedback_id' => $feedback->id,
                'user_id' => $user->id
            ]);
    
            if ($type) {
                $upvote_count = 1;
            } else {
                $downvote_count = 1;
            }
        }
    
        // Fire the event with upvote and downvote counts
        event(new \App\Events\VoteEvent($feedback, $upvote_count, $downvote_count));
    
        return response()->json(['message' => 'Vote recorded successfully.', 'feedback' => $feedback]);
    }
    
}

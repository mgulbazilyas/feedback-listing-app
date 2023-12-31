<?php

namespace App\Http\Controllers;

use App\Events\FeedbackCreatedOrUpdated;
use App\Models\Comment;
use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function index(Request $request)
    {

        $feedbacks = Feedback::orderBy('updated_at', 'desc');
        $user = $request->user();
        $showOnlyOwner = $request->input('showOnlyOwner');
        $searchTerm = $request->input('searchTerm');
        $category = $request->input('category');
        $feedbacks = $feedbacks->leftJoin('users', function($join){
            $join->on('feedback.user_id', '=', 'users.id');
        });

        if($user){
            if($showOnlyOwner=='true'){
                $feedbacks = $feedbacks->where('feedback.user_id', $user->id);
            }
            $feedbacks = $feedbacks->leftJoin('vote', function($join) use ($user) {
                $join->on('feedback.id', '=', 'vote.feedback_id')
                     ->where('vote.user_id', '=', $user->id);
            });
            $feedbacks = $feedbacks->select("feedback.*", "vote.type as vote_type", "users.name as creator_name");
        }else{
            $feedbacks = $feedbacks->select("feedback.*", "users.name as creator_name");
        }
        if($searchTerm){
            $feedbacks = $feedbacks->where(function ($query) use ($searchTerm) {
                $query->whereRaw('LOWER(title) LIKE ?', ['%' . strtolower($searchTerm) . '%'])
                      ->orWhereRaw('LOWER(description) LIKE ?', ['%' . strtolower($searchTerm) . '%']);
            });
        }
        if($category){
            $feedbacks = $feedbacks->where('feedback.category', $category);
        }
            



        $feedbacks = $feedbacks->paginate(12);
        return response()->json(['data' => $feedbacks], 200);
    }

    public function viewComments($id) {
        // Fetch the feedback with ID $id and perform your custom action
        
        // Your custom logic here...
        $res = Comment::query()
        ->where('feedback_id', $id)
        ->leftJoin('users', 'comment.user_id', '=', 'users.id')
        ->select('comment.id', 'comment.comment', 'users.email as username', 'comment.user_id')
        ->orderBy('comment.updated_at', 'desc')
        ->paginate(10);
        return response()->json($res, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'category' => 'required|string',
            // 'user_id' => 'required|exists:users,id',
        ]);
        
        $feedbackData = $request->all();
        $feedbackData['user_id'] = $request->user()->id;
        
        $feedback = Feedback::create($feedbackData);
        event(new FeedbackCreatedOrUpdated($feedback, true));
        return response()->json(['message' => 'Feedback created successfully', 'data' => $feedback], 201);
    }
    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'category' => 'required|string',
            // 'user_id' => 'required|exists:users,id',
        ]);
        $feedback = Feedback::find($id);

        $feedback->update($request->all());
        event(new FeedbackCreatedOrUpdated($feedback, false));

        return response()->json(['message' => 'Feedback Updated successfully', 'data' => $feedback], 201);
    }


    public function destroy($id)
    {
        $feedback = Feedback::find($id);

        if (!$feedback) {
            return response()->json(['message' => 'Feedback not found'], 404);
        }

        $feedback->delete();

        return response()->json(['message' => 'Feedback deleted successfully'], 200);
    }
}

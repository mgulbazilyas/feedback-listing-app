<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function index()
    {
        $feedback = Feedback::paginate(10);
        return response()->json(['data' => $feedback], 200);
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
            'user_id' => 'required|exists:users,id',
        ]);

        $feedback = Feedback::create($request->all());

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

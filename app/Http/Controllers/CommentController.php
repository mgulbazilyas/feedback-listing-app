<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'comment' => 'required|string',
            'feedback_id' => 'required|integer',
        ]);
        $comment = new Comment();
        $comment->comment = $request->input('comment');
        $comment->user_id = $request->user()->id;
        $comment->feedback_id = $request->input('feedback_id');
        $comment->save();
        return response()->json(['message' => 'Comment created successfully', 'data' => $comment], 201);
    }
    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'category' => 'required|string',
            // 'user_id' => 'required|exists:users,id',
        ]);
        $comment = Comment::find($id);

        $comment->update($request->all());

        return response()->json(['message' => 'Comment Updated successfully', 'data' => $comment], 201);
    }


    public function destroy($id)
    {
        $comment = Comment::find($id);

        if (!$comment) {
            return response()->json(['message' => 'Comment not found'], 404);
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted successfully'], 200);
    }
}

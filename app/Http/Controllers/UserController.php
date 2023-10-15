<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    //
    public function index(Request $request)
    {
        $users = User::withCount(['feedbacks', 'comments', 'votes']);
        $searchTerm = $request->input('searchTerm');
        if ($searchTerm) {
            $users = $users->where(function ($query) use ($searchTerm) {
                $query->whereRaw('LOWER(users.name) LIKE ?', ['%' . strtolower($searchTerm) . '%'])
                    ->orWhereRaw('LOWER(users.email) LIKE ?', ['%' . strtolower($searchTerm) . '%']);
            });
        }
        $users = $users->paginate(12);
        return response()->json($users);
    }

    public function store(Request $request)
    {
        $validated_data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string',
            'role' => 'required|string',
        ]);

        $feedbackData = $validated_data;

        // if(isset($feedbackData['password'])){
        $feedbackData['password'] = bcrypt($feedbackData['password']);
        // }
        $user = User::create($feedbackData);
        $user->role = $validated_data['role'];

        // Save the user to persist the role
        $user->save();
        return response()->json(['message' => 'User created successfully', 'data' => $user], 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::findorFail($id);
        $validated_data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'password' => 'string',
            'role' => 'string',
        ]);
        $feedbackData = $validated_data;
        if(isset($feedbackData['password'])){
            $feedbackData['password'] = bcrypt($feedbackData['password']);
        }
        $user->update($feedbackData);
        if(isset($feedbackData['role']))
        $user->role = $validated_data['role'];
        $user->save();
        return response()->json(['message' => 'User Updated successfully', 'data' => $user], 201);
    }
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'user not found'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'user deleted successfully'], 200);
    }
}

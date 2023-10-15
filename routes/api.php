<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::resource('feedback', FeedbackController::class);
Route::resource('votes', VoteController::class);
Route::resource('comments', CommentController::class);
Route::resource('users', UserController::class);

Route::get('feedback/{id}/comments', [FeedbackController::class, 'viewComments']);
Route::get('feedback/{id}/{type}', [VoteController::class, 'vote']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

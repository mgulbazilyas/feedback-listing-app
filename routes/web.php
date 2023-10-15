<?php

use App\Http\Controllers\ProfileController;
use App\Models\Feedback;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    $categories = Feedback::select('category')->distinct()->get();
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'categories' => $categories,
        // 'laravelVersion' => Application::VERSION,
        // 'phpVersion' => PHP_VERSION,
    ]);
})->name('homepage');
Route::get('/feedback/{id}', function (\Illuminate\Http\Request $request, $id){
    $feedbacks = Feedback::query();
    $user = $request->user();
    if($user){
        $feedbacks = $feedbacks->leftJoin('vote', function($join) use ($user) {
            $join->on('feedback.id', '=', 'vote.feedback_id')
                 ->where('vote.user_id', '=', $user->id);
        });
        $feedbacks = $feedbacks->select("feedback.*", "vote.type as vote_type");
    }else{
        $feedbacks = $feedbacks->select("feedback.*");
    }

    $feedback = $feedbacks->where('feedback.id', '=', $id)->first();
    return Inertia::render('FeedbackSingle', [
        'feedback_id' => $id,
        'feedback' => $feedback,
    
    ]);
})->name('feeback-detail');

Route::group(['middleware' => 'auth.admin'], function () {
    Route::get('/users', function () {
        return Inertia::render('Users');
    })->name('users');
    
    // Routes that require admin privileges
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

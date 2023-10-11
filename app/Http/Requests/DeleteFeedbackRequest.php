<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Feedback;

class DeleteFeedbackRequest extends FormRequest
{
    public function authorize()
    {
        $feedback = Feedback::findOrFail($this->route('feedback'));
        // TODO: add guade in auth('')
        return $feedback->user_id == auth()->id(); // Only the owner can delete feedback
    }

    public function rules()
    {
        return [];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateFeedbackRequest extends FormRequest
{
    public function authorize()
    {
        // TODO: add login required logic
        return true; // Anyone can create feedback
    }

    public function rules()
    {
        return [
            'title' => 'required|string',
            'description' => 'required|string',
            'category' => 'required|string',
        ];
    }
}

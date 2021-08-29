<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateQuestionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'question_type_id' => 'required|numeric|exists:question_types,id',
            'question_text' => 'required|string|max:10000',
            'question_score' => 'required|numeric|max:1000000',
            'can_be_shuffled' => 'nullable|boolean'
        ];
    }
}

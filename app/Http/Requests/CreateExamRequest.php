<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateExamRequest extends FormRequest
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
            'exam_name' => 'required|string|max:255',
            'needs_confirmation' => 'nullable|boolean',
            'password' => 'nullable|string|max:255',
            'start_of_exam' => 'required|date_format:Y-m-d H:i:s',
            'end_of_exam' => 'required|date_format:Y-m-d H:i:s',
            'total_score' => 'required|numeric|max:1000000',
        ];
    }
}

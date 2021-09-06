<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveScoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */

    protected $max_score;

    public function authorize()
    {
        $question = $this->route('question');
        if($question){
            $this->max_score = $question->score;
            return true;
        }
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'grade' => "required|numeric|max:$this->max_score|min:0",
        ];
    }
}
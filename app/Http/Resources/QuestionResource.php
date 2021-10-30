<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'question' => [
                'question_id' => $this->id,
                'question_text' => $this->question_text,
                'question_score' => $this->score,
                'can_be_shuffled' => $this->can_be_shuffled,
                'question_type' => [
                    'question_type_link' => route('question_types.show', $this->questionType->slug),
                     'question_type_name' => $this->questionType->name
                ]
            ]
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class QuestionGradeResource extends JsonResource
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
            'grade' => [
                'participant_id' => $this->participant_id,
                'participant_link' => route('participants.show', [$this->question->exam, $this->participant]),
                'question_id' => $this->question_id,
                'question_link' => route('questions.show', [$this->question->exam, $this->question]),
                'grade' => $this->grade,
            ]
        ];
    }
}

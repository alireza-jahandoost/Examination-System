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
                'participant_id' => $this['participant_id'],
                'question_id' => $this['question_id'],
                'grade' => $this['grade'],
            ]
        ];
    }
}

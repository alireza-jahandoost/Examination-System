<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StateResource extends JsonResource
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
            'state' => [
                'state_id' => $this->id,
                'text_part' => $this->text_answer,
                'integer_part' => $this->integer_answer,
                'question_id' => $this->question_id,
                'quesiton_link' => route('questions.show', [$this->question->exam, $this->question]),
            ]
        ];
    }
}

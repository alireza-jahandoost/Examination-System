<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class QuestionTypeResource extends JsonResource
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
            'type' => [
                'type_id' => $this->id,
                'type_name' => $this->name,
                'type_slug' => $this->slug,
                'number_of_states' => $this->number_of_states,
                'number_of_answers' => $this->number_of_answers,
                'type_of_answer' => $this->type_of_answer,
            ]
        ];
    }
}

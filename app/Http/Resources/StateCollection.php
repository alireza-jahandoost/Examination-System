<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class StateCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'states' => $this->reduce(function ($carry, $state) {
                return $carry->merge(collect([
                     [
                        'state_id' => $state->id,
                        'text_part' => $state->text_answer,
                        'integer_part' => $state->integer_answer,
                        'question_id' => $state->question_id,
                        'quesiton_link' => route('questions.show', [$state->question->exam, $state->question]),
                    ]
                ]));
            }, collect())
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class QuestionTypeCollection extends ResourceCollection
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
            'types' => $this->reduce(function ($carry, $type) {
                return $carry->merge(collect([
                     [
                        'type_id' => $type->id,
                        'type_name' => $type->name,
                        'type_slug' => $type->slug,
                        'number_of_states' => $type->number_of_states,
                        'number_of_answers' => $type->number_of_answers,
                        'type_of_answer' => $type->type_of_answer,
                    ]
                ]));
            }, collect())

        ];
    }
}

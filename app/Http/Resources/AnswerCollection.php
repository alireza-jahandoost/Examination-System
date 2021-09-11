<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class AnswerCollection extends ResourceCollection
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
            'answers' => $this->reduce(function ($carry, $answer) {
                return $carry->merge(collect([
                     [
                        'integer_part' => $answer->integer_answer,
                        'text_part' => $answer->text_answer,
                    ]
                ]));
            }, collect())
        ];
    }
}

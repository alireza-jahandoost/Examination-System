<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class QuestionCollection extends ResourceCollection
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
            'questions' => $this->reduce(function ($carry, $question) {
                return $carry->merge(collect([
                     [
                        'question_id' => $question->id,
                        'question_link' => route('questions.show', [$question->exam, $question]),
                    ]
                ]));
            }, collect())

        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AnswerResource extends JsonResource
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
            'answer' => [
                'integer_part' => $this->integer_answer,
                'text_part' => $this->text_answer,
                'grade' => $this->grade,
            ]
        ];
    }
}

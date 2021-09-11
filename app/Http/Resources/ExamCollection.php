<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ExamCollection extends ResourceCollection
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
            'exams' => $this->reduce(function ($carry, $exam) {
                return $carry->merge(collect([
                     [
                        'exam_id' => $exam->id,
                        'exam_name' => $exam->name,
                        'needs_confirmation' => $exam->confirmation_required,
                        'start_of_exam' => $exam->start,
                        'end_of_exam' => $exam->end,
                        'total_score' => $exam->total_score,
                        'creation_time' => $exam->created_at,
                        'last_update' => $exam->updated_at,
                        'owner_id' => $exam->user_id,
                        'owner_link' => route('users.show', $exam->user),

                    ]
                ]));
            }, collect())

        ];
    }
}

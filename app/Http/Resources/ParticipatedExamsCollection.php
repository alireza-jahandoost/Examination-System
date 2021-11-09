<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ParticipatedExamsCollection extends ResourceCollection
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
            'exams' => $this->reduce(function ($carry, $participant) {
                return $carry->merge(collect([
                     [
                         'exam_name' => $participant->exam->name,
                         'exam_id' => $participant->exam->id,
                         'confirmed' => ($participant->is_accepted || !$participant->exam->confirmation_required),
                         'start_of_exam' => $participant->exam->start,
                         'end_of_exam' => $participant->exam->end,
                         'total_score' => $participant->exam->total_score,
                         'owner_id' => $participant->exam->user_id,
                         'owner_name' => $participant->exam->user->name,
                         'grade' => $participant->grade,
                         'status' => $participant->status,
                    ]
                ]));
            }, collect())

        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ParticipantCollection extends ResourceCollection
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
            'participants' => $this->reduce(function ($carry, $participant) {
                return $carry->merge(collect([
                     [
                        'participant_id' => $participant->id,
                        'user_id' => $participant->user_id,
                        'user_link' => route('users.show', $participant->user),
                        'exam_link' => route('exams.show', $participant->exam),
                        'exam_id' => $participant->exam_id,
                        'confirmed' => $participant->is_accepted,
                        'status' => $participant->text_status,
                        'grade' => $participant->status === 3 ? $participant->grade : null,
                    ]
                ]));
            }, collect())

        ];
    }
}

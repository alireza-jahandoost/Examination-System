<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ParticipantResource extends JsonResource
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
            'participant' => [
                'participant_id' => $this->id,
                'user_id' => $this->user_id,
                'user_link' => route('users.show', $this->user),
                'exam_link' => route('exams.show', $this->exam),
                'exam_id' => $this->exam_id,
                'confirmed' => $this->is_accepted,
                'status' => $this->text_status,
                'grade' => $this->status === 3 ? $this->grade : null,
            ]
        ];
    }
}

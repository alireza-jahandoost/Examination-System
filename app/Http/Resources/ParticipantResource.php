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

        $change_status = [
            0 => 'NOT_FINISHED',
            1 => 'IN_PROCESSING',
            2 => 'WAIT_FOR_MANUAL_CORRECTING',
            3 => 'FINISHED',
        ];

        return [
            'participant' => [
                'participant_id' => $this->id,
                'user_id' => $this->user_id,
                'exam_id' => $this->exam_id,
                'confirmed' => $this->is_accepted,
                'status' => $change_status[$this->status],
                'grade' => $this->status === 3 ? $this->grade : null,
            ]
        ];
    }
}

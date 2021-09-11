<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ExamResource extends JsonResource
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
            'exam' => [
                'exam_id' => $this->id,
                'exam_name' => $this->name,
                'needs_confirmation' => $this->confirmation_required,
                'start_of_exam' => $this->start,
                'end_of_exam' => $this->end,
                'total_score' => $this->total_score,
                'creation_time' => $this->created_at,
                'last_update' => $this->updated_at,
                'owner_id' => $this->user_id,
                'owner_link' => route('users.show', $this->user),
            ]
        ];
    }
}

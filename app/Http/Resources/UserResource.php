<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'user' => [
                'user_id' => $this->id,
                'user_name' => $this->name,
                'user_email' => $this->email,
                'user_register_time' => $this->created_at,
            ]
        ];
    }
}

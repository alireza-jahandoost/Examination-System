<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AuthenticationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // it must be like the format used in UserResource
        return [
            'user' => [
                'user_id' => $this['user']->id,
                'user_name' => $this['user']->name,
                'user_email' => $this['user']->email,
                'user_register_time' => $this['user']->created_at,
            ],
            'token' => $this['token'],
        ];
    }
}

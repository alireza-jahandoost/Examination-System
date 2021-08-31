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
        return [
            'user' => [
                'user_id' => $this['user']->id,
                'name' => $this['user']->name,
                'email' => $this['user']->email,
            ],
            'token' => $this['token'],
        ];
    }
}

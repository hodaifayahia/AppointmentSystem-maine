<?php

namespace App\Http\Resources\Reception;

use Illuminate\Http\Resources\Json\JsonResource;

class ConventionOrganismeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this['id'],
            'organisme_name' => $this['organisme_name'],
            'description' => $this['description'],
            'industry' => $this['industry'],
            'address' => $this['address'],
            'phone' => $this['phone'],
            'email' => $this['email'],
            'website' => $this['website'],
            'conventions_count' => $this['conventions_count'],
            'conventions' => $this['conventions'],
        ];
    }
}

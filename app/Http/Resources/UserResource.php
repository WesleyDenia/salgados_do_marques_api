<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'email'       => $this->email,
            'nif'         => $this->nif,
            'phone'       => $this->phone,
            'birth_date'  => $this->birth_date?->toDateString(),
            'role'        => $this->role,
            'active'      => $this->active,
            'street'      => $this->street,
            'city'        => $this->city,
            'postal_code' => $this->postal_code,
            'theme'       => $this->theme,
            'created_at'  => $this->created_at,
            'loyalty_synced' => $this->loyalty_synced,
            'loyalty_synced_at' => $this->loyalty_synced_at,
        ];
    }
}

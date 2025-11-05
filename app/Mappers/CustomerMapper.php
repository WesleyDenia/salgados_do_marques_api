<?php
// app/Mappers/CustomerMapper.php

namespace App\Mappers;

use App\Models\User;
use App\DTOs\CustomerData;

class CustomerMapper
{
    public static function fromUser(User $user): CustomerData
    {
        return new CustomerData(
            id:         (string)$user->id,
            name:       $user->name ?: 'Cliente sem nome',
            email:      $user->email,
            taxNumber:  $user->nif,
            phone:      $user->phone,
            mobile:     $user->mobile ?? null,
            street:     $user->street,
            city:       $user->city,
            postalCode: $user->postal_code,
            countryCode:'PT',
            notes:      'Sincronizado via API Salgados do Marquês',
        );
    }
}

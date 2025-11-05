<?php

namespace App\DTOs;

class CustomerData
{
    public function __construct(
        public readonly ?string $id,            
        public readonly ?string $name,
        public readonly ?string $email,
        public readonly ?string $taxNumber,
        public readonly ?string $phone,
        public readonly ?string $mobile,
        public readonly ?string $street,
        public readonly ?string $city,
        public readonly ?string $postalCode,
        public readonly string  $countryCode = 'PT',
        public readonly ?string $notes = null,
        public readonly ?string $status = 'active',
    ) {}
}

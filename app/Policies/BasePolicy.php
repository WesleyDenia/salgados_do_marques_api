<?php

namespace App\Policies;

use App\Models\User;

class BasePolicy
{
    public function before(User $user, $ability)
    {
        if ($user->role === 'admin') {
            return true;
        }
    }
}

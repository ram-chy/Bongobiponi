<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    use CrudPolicyTrait;

    public function viewAny(User $user): bool
    {
        return true;
    }
}

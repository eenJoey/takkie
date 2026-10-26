<?php

namespace App\Policies;

use App\Models\ListCategory;
use App\Models\User;

class ListCategoryPolicy
{
    public function update(User $user, ListCategory $category): bool
    {
        return $user->isMemberOf($category->household_id);
    }

    public function delete(User $user, ListCategory $category): bool
    {
        return $user->isMemberOf($category->household_id);
    }
}

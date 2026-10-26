<?php

namespace App\Policies;

use App\Models\TodoList;
use App\Models\User;

class TodoListPolicy
{
    public function view(User $user, TodoList $list): bool
    {
        return $user->isMemberOf($list->household_id);
    }

    public function update(User $user, TodoList $list): bool
    {
        return $user->isMemberOf($list->household_id);
    }

    /** De vaste lijst "Huishoudtaken" (type tasks) kan niet verwijderd worden (Q34). */
    public function delete(User $user, TodoList $list): bool
    {
        return $list->isChecklist() && $user->isMemberOf($list->household_id);
    }
}

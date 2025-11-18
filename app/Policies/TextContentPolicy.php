<?php

namespace App\Policies;

use App\Models\TextContent;
use App\Models\User;

class TextContentPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, TextContent $textContent): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('create_content');
    }

    public function update(User $user, TextContent $textContent): bool
    {
        return $user->can('edit_content') || $textContent->version?->software?->created_by === $user->id;
    }

    public function delete(User $user, TextContent $textContent): bool
    {
        return $user->can('delete_content') || $textContent->version?->software?->created_by === $user->id;
    }
}

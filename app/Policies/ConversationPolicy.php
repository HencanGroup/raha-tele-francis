<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Conversation;
use Illuminate\Auth\Access\HandlesAuthorization;

class ConversationPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Conversation');
    }

    public function view(AuthUser $authUser, Conversation $conversation): bool
    {
        return $authUser->can('View:Conversation');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Conversation');
    }

    public function update(AuthUser $authUser, Conversation $conversation): bool
    {
        return $authUser->can('Update:Conversation');
    }

    public function delete(AuthUser $authUser, Conversation $conversation): bool
    {
        return $authUser->can('Delete:Conversation');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Conversation');
    }

    public function restore(AuthUser $authUser, Conversation $conversation): bool
    {
        return $authUser->can('Restore:Conversation');
    }

    public function forceDelete(AuthUser $authUser, Conversation $conversation): bool
    {
        return $authUser->can('ForceDelete:Conversation');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Conversation');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Conversation');
    }

    public function replicate(AuthUser $authUser, Conversation $conversation): bool
    {
        return $authUser->can('Replicate:Conversation');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Conversation');
    }

}
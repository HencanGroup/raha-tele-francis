<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\EscortResource;
use Illuminate\Auth\Access\HandlesAuthorization;

class EscortResourcePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:EscortResource');
    }

    public function view(AuthUser $authUser, EscortResource $escortResource): bool
    {
        return $authUser->can('View:EscortResource');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:EscortResource');
    }

    public function update(AuthUser $authUser, EscortResource $escortResource): bool
    {
        return $authUser->can('Update:EscortResource');
    }

    public function delete(AuthUser $authUser, EscortResource $escortResource): bool
    {
        return $authUser->can('Delete:EscortResource');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:EscortResource');
    }

    public function restore(AuthUser $authUser, EscortResource $escortResource): bool
    {
        return $authUser->can('Restore:EscortResource');
    }

    public function forceDelete(AuthUser $authUser, EscortResource $escortResource): bool
    {
        return $authUser->can('ForceDelete:EscortResource');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:EscortResource');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:EscortResource');
    }

    public function replicate(AuthUser $authUser, EscortResource $escortResource): bool
    {
        return $authUser->can('Replicate:EscortResource');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:EscortResource');
    }

}
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Escort;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class EscortPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Escort');
    }

    public function view(AuthUser $authUser, Escort $escort): bool
    {
        return $authUser->can('View:Escort');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Escort');
    }

    public function update(AuthUser $authUser, Escort $escort): bool
    {
        return $authUser->can('Update:Escort');
    }

    public function delete(AuthUser $authUser, Escort $escort): bool
    {
        return $authUser->can('Delete:Escort');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Escort');
    }

    public function restore(AuthUser $authUser, Escort $escort): bool
    {
        return $authUser->can('Restore:Escort');
    }

    public function forceDelete(AuthUser $authUser, Escort $escort): bool
    {
        return $authUser->can('ForceDelete:Escort');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Escort');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Escort');
    }

    public function replicate(AuthUser $authUser, Escort $escort): bool
    {
        return $authUser->can('Replicate:Escort');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Escort');
    }
}

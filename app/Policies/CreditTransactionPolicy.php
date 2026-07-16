<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CreditTransaction;
use Illuminate\Auth\Access\HandlesAuthorization;

class CreditTransactionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CreditTransaction');
    }

    public function view(AuthUser $authUser, CreditTransaction $creditTransaction): bool
    {
        return $authUser->can('View:CreditTransaction');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CreditTransaction');
    }

    public function update(AuthUser $authUser, CreditTransaction $creditTransaction): bool
    {
        return $authUser->can('Update:CreditTransaction');
    }

    public function delete(AuthUser $authUser, CreditTransaction $creditTransaction): bool
    {
        return $authUser->can('Delete:CreditTransaction');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CreditTransaction');
    }

    public function restore(AuthUser $authUser, CreditTransaction $creditTransaction): bool
    {
        return $authUser->can('Restore:CreditTransaction');
    }

    public function forceDelete(AuthUser $authUser, CreditTransaction $creditTransaction): bool
    {
        return $authUser->can('ForceDelete:CreditTransaction');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CreditTransaction');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CreditTransaction');
    }

    public function replicate(AuthUser $authUser, CreditTransaction $creditTransaction): bool
    {
        return $authUser->can('Replicate:CreditTransaction');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CreditTransaction');
    }

}
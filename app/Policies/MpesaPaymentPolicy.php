<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\MpesaPayment;
use Illuminate\Auth\Access\HandlesAuthorization;

class MpesaPaymentPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MpesaPayment');
    }

    public function view(AuthUser $authUser, MpesaPayment $mpesaPayment): bool
    {
        return $authUser->can('View:MpesaPayment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MpesaPayment');
    }

    public function update(AuthUser $authUser, MpesaPayment $mpesaPayment): bool
    {
        return $authUser->can('Update:MpesaPayment');
    }

    public function delete(AuthUser $authUser, MpesaPayment $mpesaPayment): bool
    {
        return $authUser->can('Delete:MpesaPayment');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:MpesaPayment');
    }

    public function restore(AuthUser $authUser, MpesaPayment $mpesaPayment): bool
    {
        return $authUser->can('Restore:MpesaPayment');
    }

    public function forceDelete(AuthUser $authUser, MpesaPayment $mpesaPayment): bool
    {
        return $authUser->can('ForceDelete:MpesaPayment');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MpesaPayment');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MpesaPayment');
    }

    public function replicate(AuthUser $authUser, MpesaPayment $mpesaPayment): bool
    {
        return $authUser->can('Replicate:MpesaPayment');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MpesaPayment');
    }

}
<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\YouthProfile;

class YouthProfilePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, YouthProfile $youthProfile): bool
    {
        return $user->getKey() === $youthProfile->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isActive()
            && $user->hasRole(UserRole::Youth)
            && ! $user->youthProfile()->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, YouthProfile $youthProfile): bool
    {
        return $user->isActive()
            && $user->hasRole(UserRole::Youth)
            && $user->getKey() === $youthProfile->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, YouthProfile $youthProfile): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, YouthProfile $youthProfile): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, YouthProfile $youthProfile): bool
    {
        return false;
    }
}

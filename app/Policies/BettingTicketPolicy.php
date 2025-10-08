<?php

namespace App\Policies;

use App\Models\BettingTicket;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BettingTicketPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // User can view their own betting tickets
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, BettingTicket $bettingTicket): bool
    {
        return $user->id === $bettingTicket->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // User can create betting tickets
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, BettingTicket $bettingTicket): bool
    {
        return $user->id === $bettingTicket->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, BettingTicket $bettingTicket): bool
    {
        return $user->id === $bettingTicket->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, BettingTicket $bettingTicket): bool
    {
        return $user->id === $bettingTicket->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, BettingTicket $bettingTicket): bool
    {
        return $user->id === $bettingTicket->user_id;
    }
}

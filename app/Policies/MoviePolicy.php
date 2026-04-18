<?php

namespace App\Policies;

use App\Models\Movie;
use App\Models\User;

class MoviePolicy
{
    /**
     * Determine whether the user can view any movies.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create movies.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the movie.
     */
    public function update(User $user, Movie $movie): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the movie.
     */
    public function delete(User $user, Movie $movie): bool
    {
        return false; // Tuyệt đối không xóa khỏi Database
    }
}

<?php

namespace App\Policies;

use App\Models\Participant;
use App\Models\User;
use App\Models\Exam;
use Illuminate\Auth\Access\HandlesAuthorization;

class ParticipantPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user, Exam $exam)
    {
        return $user->id === $exam->user_id;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Participant  $participant
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Participant $participant, Exam $exam)
    {
        return ($user->id === $exam->user_id && $participant->exam_id === $exam->id);
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user, Exam $exam)
    {
        return $user->id !== $exam->user_id;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Participant  $participant
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Participant $participant)
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Participant  $participant
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Participant $participant)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Participant  $participant
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Participant $participant)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Participant  $participant
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Participant $participant)
    {
        //
    }
    
    public function accept(User $user, Exam $exam)
    {
        return $user->id === $exam->user_id;
    }
}

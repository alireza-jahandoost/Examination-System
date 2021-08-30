<?php

namespace App\Policies;

use App\Models\State;
use App\Models\User;
use App\Models\Exam;
use App\Models\Question;
use Illuminate\Auth\Access\HandlesAuthorization;

class StatePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user, Exam $exam, Question $question)
    {
        return ($exam->user_id === $user->id && $exam->id === $question->exam_id);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\State  $state
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, State $state, Exam $exam, Question $question)
    {
        return ($exam->user_id === $user->id && $exam->id === $question->exam_id && $question->id === $state->question_id);
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user, Exam $exam, Question $question)
    {
        if($exam->published){
            return false;
        }
        return ($user->id === $exam->user_id && $exam->id === $question->exam_id);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\State  $state
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, State $state, Exam $exam, Question $question)
    {
        if($exam->published){
            return false;
        }
        return ($user->id === $exam->user_id && $question->exam_id === $exam->id && $question->id === $state->question_id);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\State  $state
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, State $state, Exam $exam, Question $question)
    {
        if($exam->published){
            return false;
        }
        return ($user->id === $exam->user_id && $exam->id === $question->exam_id && $state->question_id === $question->id);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\State  $state
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, State $state)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\State  $state
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, State $state)
    {
        //
    }
}

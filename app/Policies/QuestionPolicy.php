<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\User;
use App\Models\Exam;
use App\Models\Participant;
use Illuminate\Auth\Access\HandlesAuthorization;

use Carbon\Carbon;

class QuestionPolicy
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
        if ($user->id !== $exam->user_id) {
            if (!$exam->published) {
                return false;
            }

            $start = Carbon::make($exam->start);
            $user_participant = Participant::where([
                'user_id' => $user->id,
                'exam_id' => $exam->id
                ])->first();
            if (Carbon::now() >= $start && $user_participant) {
                if ($exam->confirmation_required) {
                    if ($user_participant->is_accepted) {
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return true;
                }
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Question  $question
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Question $question, Exam $exam)
    {
        if ($exam->user_id === $user->id) {
            return $question->exam_id === $exam->id;
        } else {
            if (!$exam->published) {
                return false;
            }
            $start = Carbon::make($exam->start);
            $participant = Participant::where(['exam_id' => $exam->id, 'user_id' => $user->id])->first();
            if (!$participant) {
                return false;
            }
            if (Carbon::now()>=$start) {
                if ($exam->confirmation_required) {
                    return $participant->is_accepted;
                } else {
                    return true;
                }
            } else {
                return false;
            }
        }
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user, Exam $exam)
    {
        if ($exam->published) {
            return false;
        }
        return $exam->user_id === $user->id;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Question  $question
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Question $question, Exam $exam)
    {
        if ($exam->published) {
            return false;
        }
        return ($exam->user_id === $user->id && $question->exam_id === $exam->id);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Question  $question
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Question $question, Exam $exam)
    {
        if ($exam->published) {
            return false;
        }
        return ($exam->user_id === $user->id && $question->exam_id === $exam->id);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Question  $question
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Question $question)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Question  $question
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Question $question)
    {
        //
    }
}

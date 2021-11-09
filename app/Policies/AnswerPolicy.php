<?php

namespace App\Policies;

use App\Models\Answer;
use App\Models\User;
use App\Models\Question;
use App\Models\Participant;
use Illuminate\Auth\Access\HandlesAuthorization;

use Carbon\Carbon;

class AnswerPolicy
{
    use HandlesAuthorization;

    protected function isParticipantFinishedTheExam($participant)
    {
        return $participant->status !== 0;
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user, Question $question, Participant $participant)
    {
        if ($user->id === $question->exam->user_id) {
            if ($participant->exam_id === $question->exam_id) {
                return true;
            } else {
                return false;
            }
        }
        if ($participant->user_id === auth()->id() && $question->exam_id === $participant->exam_id) {
            $exam = $question->exam;
            $start = Carbon::make($exam->start);
            if ($start <= Carbon::now()) {
                if ($exam->confirmation_required) {
                    if ($participant->is_accepted) {
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
            return false;
        }
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Answer  $answer
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Answer $answer)
    {
        //
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user, Question $question)
    {
        $exam = $question->exam;

        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $question->exam_id,
        ])->first();
        if ($participant) {
            if ($this->isParticipantFinishedTheExam($participant)) {
                return false;
            }
            $start = Carbon::make($exam->start);
            $end = Carbon::make($exam->end);
            if ($start <= Carbon::now() && $end >= Carbon::now()) {
                if ($exam->confirmation_required) {
                    if ($participant->is_accepted) {
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
            return false;
        }
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Answer  $answer
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Answer $answer)
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Answer  $answer
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Question $question)
    {
        $exam = $question->exam;
        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $question->exam_id,
        ])->first();
        if ($participant) {
            if ($this->isParticipantFinishedTheExam($participant)) {
                return false;
            }
            $start = Carbon::make($exam->start);
            $end = Carbon::make($exam->end);
            if ($start <= Carbon::now() && $end >= Carbon::now()) {
                if ($exam->confirmation_required) {
                    if ($participant->is_accepted) {
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
            return false;
        }
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Answer  $answer
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Answer $answer)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Answer  $answer
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Answer $answer)
    {
        //
    }
}

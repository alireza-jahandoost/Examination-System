<?php

namespace App\Policies;

use App\Models\Participant;
use App\Models\User;
use App\Models\Exam;
use App\Models\Question;
use Illuminate\Auth\Access\HandlesAuthorization;

use Carbon\Carbon;

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
        if ($user->id === $participant->user_id) {
            return true;
        }
        if ($user->id === $exam->user_id) {
            if ($participant->exam_id === $exam->id) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user, Exam $exam)
    {
        $participant_registered = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $exam->id,
        ])->exists();

        $end = Carbon::make($exam->end);
        if ($exam->published) {
            if ($end > Carbon::now()) {
                if (! $participant_registered) {
                    if ($user->id !== $exam->user_id) {
                        return true;
                    }
                }
            }
        }

        return false;
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
        if ($exam->confirmation_required) {
            if ($user->id === $exam->user_id) {
                $end = Carbon::make($exam->end);
                if ($end >= Carbon::now()) {
                    return true;
                }
            }
        }
        return false;
    }

    public function finishExam(User $user, Exam $exam)
    {
        $participant = Participant::where([
            'user_id' => $user->id,
            'exam_id' => $exam->id,
        ])->first();

        if ($participant) {
            $start = Carbon::make($exam->start);
            $end = Carbon::make($exam->end);
            if ($end >= Carbon::now() && $start <= Carbon::now()) {
                if ($exam->confirmation_required) {
                    if ($participant->is_accepted) {
                        return $participant->status === 0;
                    } else {
                        return false;
                    }
                } else {
                    return $participant->status === 0;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function saveScore(User $user, Participant $participant, Question $question)
    {
        if ($user->id === $question->exam->user_id) {
            if ($participant->exam_id === $question->exam_id) {
                if ($participant->status === 2 || $participant->status === 3) {
                    return true;
                }
            }
        }
        return false;
    }

    public function questionGrade(User $user, Participant $participant, Question $question)
    {
        if ($participant->status !== 2 && $participant->status !== 3) {
            return false;
        }
        if ($user->id === $participant->user_id) {
            if ($participant->exam_id === $question->exam_id) {
                return true;
            }
        }
        if ($user->id === $question->exam->user_id) {
            if ($participant->exam_id === $question->exam_id) {
                return true;
            }
        }
        return false;
    }

    public function participatedExams(User $user)
    {
        return true;
    }
}

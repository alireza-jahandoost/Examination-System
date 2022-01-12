<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Exam;
use App\Models\Participant;
use App\Jobs\CorrectExamJob;
use Carbon\Carbon;

class FinishExamController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $exams = Exam::where(function ($query) {
            return $query->where('all_participants_auto_corrected', 0)->where('published', 1)->whereDate('end', '<', Carbon::now()->toDateString());
        })->orWhere(function ($query) {
            return $query->where('all_participants_auto_corrected', 0)->where('published', 1)->whereDate('end', '=', Carbon::now()->toDateString())->whereTime('end', '<', Carbon::now()->toTimeString());
        })->get();
        foreach ($exams as $exam) {
            $empty = true;
            Participant::where('exam_id', $exam->id)->where('status', 0)->chunk(100, function ($participants) use (&$empty, $exam) {
                foreach ($participants as $participant) {
                    if ($exam->confirmation_required && !$participant->is_accepted) {
                        continue;
                    }
                    $empty = false;
                    $participant->status = 1;
                    $participant->save();
                    CorrectExamJob::dispatch($participant);
                }
            });

            if (Participant::where('exam_id', $exam->id)->where('status', '<=', 1)->doesntExist()) {
                $exam->all_participants_auto_corrected = 1;
                $exam->save();
            }
        }
    }
}

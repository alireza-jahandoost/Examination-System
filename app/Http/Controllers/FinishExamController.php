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
        dump('in');
        $exams = Exam::where(function ($query) {
            return $query->where('all_participants_auto_corrected', 0)->whereDate('end', '<', Carbon::now()->toDateString());
        })->orWhere(function ($query) {
            return $query->where('all_participants_auto_corrected', 0)->whereDate('end', '=', Carbon::now()->toDateString())->whereTime('end', '<', Carbon::now()->toTimeString());
        })->get();
        dump($exams);
        foreach ($exams as $exam) {
            dump('in there');
            $empty = true;
            Participant::where('exam_id', $exam->id)->where('status', 0)->chunk(100, function ($participants) use (&$empty) {
                foreach ($participants as $participant) {
                    dump('participant: ', $participant);
                    $empty = false;
                    CorrectExamJob::dispatch($participant);
                }
            });
            dump('empty', $empty);

            if ($empty) {
                $exam->all_participants_auto_corrected = true;
                $exam->save();
            }
        }
    }
}

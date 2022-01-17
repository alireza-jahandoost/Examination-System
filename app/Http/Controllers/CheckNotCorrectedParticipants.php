<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Participant;

class CheckNotCorrectedParticipants extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        Participant::where('status', 1)->where('status_changed_at', '<=', Carbon::now()->subMinutes(10)->toDateTimeString())->chunk(100, function ($participants) {
            foreach ($participants as $participant) {
                $participant->status = 0;
                $participant->save();
            }
        });
    }
}

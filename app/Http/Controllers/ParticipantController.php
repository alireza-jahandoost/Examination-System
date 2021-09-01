<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Http\Request;

use App\Http\Requests\CreateParticipantRequest;
use App\Http\Requests\AcceptParticipantRequest;

use App\Actions\Participants\CanUserRegisterInExam;

use App\Http\Resources\MessageResource;
use App\Http\Resources\ParticipantResource;
use App\Http\Resources\ParticipantCollection;

class ParticipantController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Exam $exam)
    {
        $this->authorize('viewAny', [Participant::class, $exam]);
        return (new ParticipantCollection($exam->participants()->paginate()))->response()->setStatusCode(200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateParticipantRequest $request, Exam $exam, CanUserRegisterInExam $action)
    {
        $this->authorize('create', [Participant::class, $exam]);
        $status = $action->check($exam, $request->validated());
        if($status !== 'success'){
            return (new MessageResource([
                'message' => $status
                ]))->response()->setStatusCode(401);
        }
        $participant = new Participant;
        $participant->user_id = auth()->id();
        $participant->exam_id = $exam->id;
        $participant->save();

        return response(null, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Participant  $participant
     * @return \Illuminate\Http\Response
     */
    public function show(Exam $exam, Participant $participant)
    {
        $this->authorize('view', [$participant, $exam]);
        return (new ParticipantResource($participant))->response()->setStatusCode(200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Participant  $participant
     * @return \Illuminate\Http\Response
     */
    public function edit(Participant $participant)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Participant  $participant
     * @return \Illuminate\Http\Response
     */
    public function update(AcceptParticipantRequest $request, Exam $exam)
    {
        $this->authorize('accept', [Participant::class, $exam]);
        $data = $request->validated();
        $user = User::find($request->input('user_id'));
        $participant = Participant::where(['user_id' => $user->id, 'exam_id' => $exam->id]);

        if($participant->exists()){
            $participant = $participant->first();
            $participant->is_accepted = true;
            $participant->save();

            return response(null, 202);
        }
        abort(404);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Participant  $participant
     * @return \Illuminate\Http\Response
     */
    public function destroy(Participant $participant)
    {
        //
    }
}

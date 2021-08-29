<?php

namespace App\Http\Controllers;

use App\Models\State;
use App\Models\User;
use App\Models\Exam;
use App\Models\Question;
use Illuminate\Http\Request;

use App\Http\Requests\CreateStateRequest;
use App\Http\Requests\UpdateStateRequest;

use App\Http\Resources\MessageResource;
use App\Http\Resources\StateResource;
use App\Http\Resources\StateCollection;

use App\Actions\States\CanUserCreateState;
use App\Actions\States\CanUserUpdateState;

class StateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Exam $exam, Question $question)
    {
        $this->authorize('viewAny', [State::class, $exam, $question]);
        return (new StateCollection($question->states))->response()->setStatusCode(200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\CreateStateRequest  $request
     * @param  \App\Models\Exam  $exam
     * @param  \App\Models\Question  $question
     * @return \Illuminate\Http\Response
     */
    public function store(CreateStateRequest $request,CanUserCreateState $action, Exam $exam, Question $question)
    {
        $this->authorize('create', [State::class, $exam, $question]);
        $data = $request->validated();

        $status = $action->check($question, $data);
        if($status !== 'success'){
            return (new MessageResource([
                'message' => $status
            ]))->response()->setStatusCode(401);
        }

        $state = $question->states()->create([
            'text_answer' => $data['text_part'] ?? null,
            'integer_answer' => $data['integer_part'] ?? null
        ]);
        return (new StateResource($state))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\State  $state
     * @return \Illuminate\Http\Response
     */
    public function show(Exam $exam, Question $question, State $state)
    {
        $this->authorize('view', [$state, $exam, $question]);
        return (new StateResource($state))->response()->setStatusCode(200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\State  $state
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateStateRequest $request,CanUserUpdateState $action, Exam $exam, Question $question,  State $state)
    {
        $this->authorize('update', [$state, $exam, $question]);
        $data = $request->validated();
        $status = $action->check($question, $data);
        if($status !== 'success'){
            return (new MessageResource([
                'message' => $status
                ]))->response()->setStatusCode(401);
        }

        if(isset($data['text_part']))
            $state->text_answer = $data['text_part'];
        if(isset($data['integer_part']))
            $state->integer_answer = $data['integer_part'];

        $state->save();

        return (new StateResource($state))->response()->setStatusCode(200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\State  $state
     * @return \Illuminate\Http\Response
     */
    public function destroy(Exam $exam, Question $question, State $state)
    {
        $this->authorize('delete', [$state, $exam, $question]);
        $state->delete();
        return response(null, 202);
    }
}

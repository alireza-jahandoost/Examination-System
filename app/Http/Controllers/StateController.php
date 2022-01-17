<?php

namespace App\Http\Controllers;

use App\Models\State;
use App\Models\Exam;
use App\Models\Question;
use Illuminate\Http\Request;

use App\Http\Requests\CreateStateRequest;
use App\Http\Requests\UpdateStateRequest;

use App\Http\Resources\StateResource;
use App\Http\Resources\StateCollection;

use App\Actions\States\CanUserCreateState;
use App\Actions\States\CanUserUpdateState;

class StateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Exam $exam, Question $question)
    {
        $this->authorize('viewAny', [State::class, $exam, $question]);
        $states = $question->states()->with('question')->orderBy('id')->get();
        if (count($states)>1 && $question->questionType->name === 'ordering' && $exam->user_id !== auth()->id()) {
            $newStates = $states->shuffle();
            $check = true;
            for ($i = 0;$i < count($newStates);$i++) {
                if ($newStates[$i]->id !== $states[$i]->id) {
                    $check = false;
                    break;
                }
            }
            $states = $check === true ? $newStates->reverse() : $newStates;
        }
        return (new StateCollection($states))->response()->setStatusCode(200);
    }

    /**
     * store a new state for question
     * @param  CreateStateRequest $request
     * @param  CanUserCreateState $action
     * @param  Exam               $exam
     * @param  Question           $question
     */
    public function store(CreateStateRequest $request, CanUserCreateState $action, Exam $exam, Question $question)
    {
        $this->authorize('create', [State::class, $exam, $question]);
        $data = $request->validated();

        $status = $action->check($question, $data);
        if ($status !== 'success') {
            return response()->json([
                'message' => $status
            ], 422);
        }

        $state = $question->states()->create([
            'text_answer' => $data['text_part'] ?? null,
            'integer_answer' => $data['integer_part'] ?? null
        ]);
        return (new StateResource($state))->response()->setStatusCode(201);
    }

    /**
     * show a specific state of question
     * @param  Exam     $exam
     * @param  Question $question
     * @param  State    $state
     */
    public function show(Exam $exam, Question $question, State $state)
    {
        $this->authorize('view', [$state, $exam, $question]);
        return (new StateResource($state))->response()->setStatusCode(200);
    }

    /**
     * update an specific state of question
     * @param  UpdateStateRequest $request
     * @param  CanUserUpdateState $action
     * @param  Exam               $exam
     * @param  Question           $question
     * @param  State              $state
     */
    public function update(UpdateStateRequest $request, CanUserUpdateState $action, Exam $exam, Question $question, State $state)
    {
        $this->authorize('update', [$state, $exam, $question]);
        $data = $request->validated();
        $status = $action->check($question, $data);
        if ($status !== 'success') {
            return response()->json([
                'message' => $status
            ], 422);
        }

        if (isset($data['text_part'])) {
            $state->text_answer = $data['text_part'];
        }
        if (isset($data['integer_part'])) {
            $state->integer_answer = $data['integer_part'];
        }

        $state->save();

        return (new StateResource($state))->response()->setStatusCode(200);
    }

    /**
     * destroy a state
     * @param  Exam     $exam
     * @param  Question $question
     * @param  State    $state
     */
    public function destroy(Exam $exam, Question $question, State $state)
    {
        $this->authorize('delete', [$state, $exam, $question]);
        if ($question->questionType->name === 'ordering') {
            $question->states->each(function ($currentState) use ($state) {
                if ($currentState->integer_answer > $state->integer_answer) {
                    $currentState->integer_answer --;
                    $currentState->save();
                }
            });
        }
        $state->delete();
        return response(null, 202);
    }
}

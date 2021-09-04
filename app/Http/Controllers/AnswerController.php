<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Participant;
use Illuminate\Http\Request;

use App\Http\Requests\CreateAnswerRequest;

use App\Http\Resources\MessageResource;
use App\Http\Resources\AnswerResource;
use App\Http\Resources\AnswerCollection;

use App\Actions\Answers\CanUserCreateTheAnswer;

class AnswerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Question $question, Participant $participant)
    {
        $this->authorize('viewAny', [Answer::class, $question, $participant]);
        $answers = Answer::where([
            'question_id' => $question->id,
            'participant_id' => $participant->id,
            ])->get();
        return (new AnswerCollection($answers))->response()->setStatusCode(200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateAnswerRequest $request, Question $question, CanUserCreateTheAnswer $action)
    {
        $this->authorize('create', [Answer::class, $question]);
        $data = $request->validated();
        $check = $action->check($question, $data);
        if($check['message'] !== 'success'){
            return (new MessageResource([
                'message' => $check['message'],
            ]))->response()->setStatusCode($check['status']);
        }

        $answer = new Answer;
        $participant = Participant::where([
            'user_id' => auth()->id(),
            'exam_id' => $question->exam_id,
        ])->first();
        $answer->participant_id = $participant->id;
        $answer->question_id = $question->id;
        if(isset($data['integer_part']))
            $answer->integer_answer = $data['integer_part'];
        if(isset($data['text_part']))
            $answer->text_answer = $data['text_part'];

        $answer->save();
        return response(null, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Answer  $answer
     * @return \Illuminate\Http\Response
     */
    public function show(Answer $answer)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Answer  $answer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Answer $answer)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Answer  $answer
     * @return \Illuminate\Http\Response
     */
    public function destroy(Question $question)
    {
        $this->authorize('delete', [Answer::class, $question]);
        $participant = Participant::where([
            'user_id' => auth()->id(),
            'exam_id' => $question->exam_id,
        ])->first();
        Answer::where([
            'question_id' => $question->id,
            'participant_id' => $participant->id,
        ])->delete();
        return response(null, 202);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Exam;
use App\Models\QuestionType;

use Illuminate\Http\Request;

use App\Http\Requests\CreateQuestionRequest;
use App\Http\Requests\UpdateQuestionRequest;

use App\Http\Resources\QuestionResource;
use App\Http\Resources\QuestionCollection;

class QuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Exam $exam)
    {
        $this->authorize('viewAny', [Question::class, $exam]);
        return (new QuestionCollection($exam->questions))->response()->setStatusCode(200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Exam $exam, CreateQuestionRequest $request)
    {
        $this->authorize('create', [Question::class, $exam]);
        $data = $request->validated();
        $question_type = QuestionType::findOrFail($data['question_type_id']);

        $question = $exam->questions()->make([
            'question_text' => $data['question_text'],
            'score' => $data['question_score'],
        ]);

        $question->question_type_id = $data['question_type_id'];

        if(isset($data['can_be_shuffled'])){
            $question->can_be_shuffled = $data['can_be_shuffled'];
        }
        $question->save();

        return (new QuestionResource($question))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Question  $question
     * @return \Illuminate\Http\Response
     */
    public function show(Exam $exam, Question $question)
    {
        $this->authorize('view', [$question, $exam]);
        return (new QuestionResource($question))->response()->setStatusCode(200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Question  $question
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateQuestionRequest $request, Exam $exam, Question $question)
    {
        $this->authorize('update', [$question, $exam]);
        $data = $request->validated();

        if(isset($data['question_text']))
            $question->question_text = $data['question_text'];
        if(isset($data['question_score']))
            $question->score = $data['question_score'];
        if(isset($data['can_be_shuffled']))
            $question->can_be_shuffled = $data['can_be_shuffled'];

        $question->save();

        return (new QuestionResource($question))->response()->setStatusCode(200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Question  $question
     * @return \Illuminate\Http\Response
     */
    public function destroy(Exam $exam, Question $question)
    {
        $this->authorize('delete', [$question, $exam]);
        $question->delete();
        return response(null, 202);
    }
}

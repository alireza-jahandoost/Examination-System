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
     * index questions of specific exam
     * @param  Exam   $exam
     */
    public function index(Exam $exam)
    {
        $this->authorize('viewAny', [Question::class, $exam]);
        return (new QuestionCollection($exam->questions()->orderBy('id')->get()))->response()->setStatusCode(200);
    }

    /**
     * store a question for specific exam
     * @param  Exam                  $exam
     * @param  CreateQuestionRequest $request
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

        if (isset($data['can_be_shuffled'])) {
            $question->can_be_shuffled = $data['can_be_shuffled'];
        }
        $question->save();

        return (new QuestionResource($question))->response()->setStatusCode(201);
    }

    /**
     * show a specific question of Exam
     * @param  Exam     $exam
     * @param  Question $question
     */
    public function show(Exam $exam, Question $question)
    {
        $this->authorize('view', [$question, $exam]);
        return (new QuestionResource($question))->response()->setStatusCode(200);
    }

    /**
     * update a question
     * @param  UpdateQuestionRequest $request
     * @param  Exam                  $exam
     * @param  Question              $question
     */
    public function update(UpdateQuestionRequest $request, Exam $exam, Question $question)
    {
        $this->authorize('update', [$question, $exam]);
        $data = $request->validated();

        if (isset($data['question_text'])) {
            $question->question_text = $data['question_text'];
        }
        if (isset($data['question_score'])) {
            $question->score = $data['question_score'];
        }
        if (isset($data['can_be_shuffled'])) {
            $question->can_be_shuffled = $data['can_be_shuffled'];
        }

        $question->save();

        return (new QuestionResource($question))->response()->setStatusCode(200);
    }

    /**
     * delete question
     * @param  Exam     $exam
     * @param  Question $question
     */
    public function destroy(Exam $exam, Question $question)
    {
        $this->authorize('delete', [$question, $exam]);
        $question->delete();
        return response(null, 202);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Participant;

use App\Http\Requests\CreateAnswerRequest;

use App\Http\Resources\AnswerResource;
use App\Http\Resources\AnswerCollection;

use App\Actions\Answers\CanUserCreateTheAnswer;

class AnswerController extends Controller
{
    /**
     * index answers of a question of participant
     * @param  Question         $question
     * @param  Participant      $participant
     */
    public function index(Question $question, Participant $participant)
    {
        $this->authorize('viewAny', [Answer::class, $question, $participant]);
        $answers = Answer::where([
            'question_id' => $question->id,
            'participant_id' => $participant->id,
            ])->orderBy('id')->get();
        return (new AnswerCollection($answers))->response()->setStatusCode(200);
    }

    /**
     * store new answer
     * @param  CreateAnswerRequest    $request
     * @param  Question               $question
     * @param  CanUserCreateTheAnswer $action
     */
    public function store(CreateAnswerRequest $request, Question $question, CanUserCreateTheAnswer $action)
    {
        $this->authorize('create', [Answer::class, $question]);
        $data = $request->validated();
        $check = $action->check($question, $data);
        if ($check['message'] !== 'success') {
            return response()->json([
                'message' => $check['message']
            ], $check['status']);
        }

        $answer = new Answer();
        $participant = Participant::where([
            'user_id' => auth()->id(),
            'exam_id' => $question->exam_id,
        ])->first();
        $answer->participant_id = $participant->id;
        $answer->question_id = $question->id;
        if (isset($data['integer_part'])) {
            $answer->integer_answer = $data['integer_part'];
        }
        if (isset($data['text_part'])) {
            $answer->text_answer = $data['text_part'];
        }

        $answer->save();
        return response(null, 201);
    }

    /**
     * delete all the question Answers
     * @param  Question $question
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

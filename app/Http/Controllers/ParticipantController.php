<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Models\Exam;
use App\Models\User;
use App\Models\Question;
use App\Models\Answer;
use App\Models\QuestionGrade;
use Illuminate\Http\Request;

use App\Http\Requests\CreateParticipantRequest;
use App\Http\Requests\AcceptParticipantRequest;
use App\Http\Requests\SaveScoreRequest;

use App\Actions\Participants\CanUserRegisterInExam;
use App\Actions\Correcting\AreManualQuestionsScored;

use App\Http\Resources\MessageResource;
use App\Http\Resources\ParticipantResource;
use App\Http\Resources\QuestionGradeResource;
use App\Http\Resources\ParticipantCollection;

use App\Jobs\CorrectExamJob;

use App\Actions\Correcting\CalculateQuestionGrade;

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

    public function finish_exam(Exam $exam)
    {
        $this->authorize('finishExam', [Participant::class, $exam]);
        $participant = Participant::where([
            'user_id' => auth()->id(),
            'exam_id' => $exam->id
        ])->first();
        $participant->status = 1;
        $participant->save();

        CorrectExamJob::dispatch($participant);

        return response(null, 202);
    }

    public function save_score(SaveScoreRequest $request, Question $question, Participant $participant, AreManualQuestionsScored $action)
    {
        $this->authorize('saveScore', [$participant, $question]);
        $data = $request->validated();

        switch($question->questionType->id){
            case 1:
                $questionGrade = QuestionGrade::where([
                    'participant_id' => $participant->id,
                    'question_id' => $question->id,
                ])->first();

                if(!$questionGrade){
                    $questionGrade = new QuestionGrade;
                    $questionGrade->participant_id = $participant->id;
                    $questionGrade->question_id = $question->id;
                }

                $questionGrade->grade = $data['grade'];
                $questionGrade->save();

                $participant->recalculateGrade();

                if($action->check($question->exam, $participant)){
                    $participant->status = 3;
                }

                $participant->save();

                return response(null, 202);

            default:
                dd('unknown type of question(ParticipantController)');
                break;
        }
    }

    public function question_grade(Participant $participant, Question $question, CalculateQuestionGrade $action)
    {
        $this->authorize('questionGrade', [$participant, $question]);
        $grade = $participant->grades()->where('question_id', $question->id)->first()->grade;

        return (new QuestionGradeResource([
            'participant_id' => $participant->id,
            'question_id' => $question->id,
            'grade' => $grade,
        ]))->response()->setStatusCode(200);
    }
}

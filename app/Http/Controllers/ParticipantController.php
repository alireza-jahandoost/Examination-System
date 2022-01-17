<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Models\Exam;
use App\Models\User;
use App\Models\Question;
use App\Models\QuestionGrade;
use Illuminate\Http\Request;

use App\Http\Requests\CreateParticipantRequest;
use App\Http\Requests\AcceptParticipantRequest;
use App\Http\Requests\SaveScoreRequest;

use App\Actions\Participants\CanUserRegisterInExam;
use App\Actions\Correcting\AreManualQuestionsScored;

use App\Http\Resources\ParticipantResource;
use App\Http\Resources\QuestionGradeResource;
use App\Http\Resources\ParticipantCollection;
use App\Http\Resources\ParticipatedExamsCollection;

use App\Jobs\CorrectExamJob;

use App\Actions\Correcting\CalculateQuestionGrade;

class ParticipantController extends Controller
{
    /**
     * index the participants of an exam
     * @param  Exam   $exam
     */
    public function index(Exam $exam)
    {
        $this->authorize('viewAny', [Participant::class, $exam]);
        return (new ParticipantCollection($exam->participants()->orderBy('id')->paginate()))->response()->setStatusCode(200);
    }

    /**
     * store new participant
     * @param  CreateParticipantRequest $request
     * @param  Exam                     $exam
     * @param  CanUserRegisterInExam    $action
     */
    public function store(CreateParticipantRequest $request, Exam $exam, CanUserRegisterInExam $action)
    {
        $this->authorize('create', [Participant::class, $exam]);
        $status = $action->check($exam, $request->validated());
        if ($status !== 'success') {
            return response()->json([
                'message' => "The given data was invalid.",
                'errors' => [
                    'password' => $status
                ]
            ], 422);
        }
        $participant = new Participant();
        $participant->user_id = auth()->id();
        $participant->exam_id = $exam->id;
        $participant->save();

        return response(null, 201);
    }

    /**
     * show participant
     * @param  Exam        $exam
     * @param  Participant $participant
     */
    public function show(Exam $exam, Participant $participant)
    {
        $this->authorize('view', [$participant, $exam]);
        return (new ParticipantResource($participant))->response()->setStatusCode(200);
    }

    /**
     * accept participant if exam needs confirmation
     * @param  AcceptParticipantRequest $request
     * @param  Exam                     $exam
     */
    public function update(AcceptParticipantRequest $request, Exam $exam)
    {
        $this->authorize('accept', [Participant::class, $exam]);
        $data = $request->validated();
        $user = User::find($request->input('user_id'));
        $participant = Participant::where(['user_id' => $user->id, 'exam_id' => $exam->id]);

        if ($participant->exists()) {
            $participant = $participant->first();
            $participant->is_accepted = true;
            $participant->save();

            return response(null, 202);
        }
        abort(404);
    }

    /**
     * make the exam of current user finished
     * @param  Exam   $exam
     */
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

    /**
     * save score for manual correcting questions by owner of exam
     * @param  SaveScoreRequest         $request
     * @param  Question                 $question
     * @param  Participant              $participant
     * @param  AreManualQuestionsScored $action
     */
    public function save_score(SaveScoreRequest $request, Question $question, Participant $participant, AreManualQuestionsScored $action)
    {
        $this->authorize('saveScore', [$participant, $question]);
        $data = $request->validated();

        $questionGrade = QuestionGrade::where([
                    'participant_id' => $participant->id,
                    'question_id' => $question->id,
                ])->first();

        if (!$questionGrade) {
            $questionGrade = new QuestionGrade();
            $questionGrade->participant_id = $participant->id;
            $questionGrade->question_id = $question->id;
        }

        $questionGrade->grade = $data['grade'];
        $questionGrade->save();

        $participant->recalculateGrade();

        if ($action->check($question->exam, $participant)) {
            $participant->status = 3;
        }

        $participant->save();

        return response(null, 202);
    }

    /**
     * show the grade of question for participant
     * @param  Participant            $participant
     * @param  Question               $question
     * @param  CalculateQuestionGrade $action
     */
    public function question_grade(Participant $participant, Question $question, CalculateQuestionGrade $action)
    {
        $this->authorize('questionGrade', [$participant, $question]);
        $grade = $participant->grades()->where('question_id', $question->id)->first();
        if (!$grade) {
            $grade = new QuestionGrade();
            $grade->grade = null;
            $grade->participant_id = $participant->id;
            $grade->question_id = $question->id;
        }

        return (new QuestionGradeResource($grade))->response()->setStatusCode(200);
    }

    /**
     * get all the exams that user participated into
     */
    public function participated_exams()
    {
        $this->authorize('participatedExams', Participant::class);

        return (new ParticipatedExamsCollection(auth()->user()->participatedExams()->with('exam.user')->orderBy('created_at', 'desc')->paginate(20)))->response()->setStatusCode(200);
    }

    /**
     * get current authenticated participant in this exam
     */
    public function current_participant(Exam $exam)
    {
        $participant = Participant::where(['exam_id' => $exam->id, 'user_id' => auth()->id()])->first();
        if (!$participant) {
            abort(404);
        }
        return (new ParticipantResource($participant))->response()->setStatusCode(200);
    }
}

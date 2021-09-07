<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use Illuminate\Http\Request;

use App\Http\Requests\CreateExamRequest;
use App\Http\Requests\UpdateExamRequest;

use App\Http\Resources\ExamResource;
use App\Http\Resources\ExamCollection;
use App\Http\Resources\MessageResource;

use App\Actions\Exams\CanExamBePublished;

class ExamController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(Exam::class, 'exam');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $query = Exam::where('published', true)->paginate();
        return (new ExamCollection($query))->response()->setStatusCode(200);
    }

    public function index_own()
    {
        $query = auth()->user()->ownedExams()->paginate();
        return (new ExamCollection($query))->response()->setStatusCode(200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateExamRequest $request)
    {
        $data = $request->validated();

        $exam = auth()->user()->ownedExams()->create([
            'name' => $data['exam_name'],
            'start' => $data['start_of_exam'],
            'end' => $data['end_of_exam'],
            'total_score' => $data['total_score'],
        ]);
        if(isset($data['needs_confirmation']))
            $exam->confirmation_required = $data['needs_confirmation'];

        if(isset($data['password']))
            $exam->password = $data['password'];

        $exam->save();

        return (new ExamResource($exam))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Exam  $exam
     * @return \Illuminate\Http\Response
     */
    public function show(Exam $exam)
    {
        return (new ExamResource($exam))->response()->setStatusCode(200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Exam  $exam
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateExamRequest $request, Exam $exam)
    {
        $data = $request->validated();
        $exam->update([
            'name' => $data['exam_name'] ?? $exam->name,
            'confirmation_required' => $data['needs_confirmation'] ?? $exam->confirmation_required,
            'start' => $data['start_of_exam'] ?? $exam->start,
            'end' => $data['end_of_exam'] ?? $exam->end,
            'total_score' => $data['total_score'] ?? $exam->total_score,
        ]);
        if(isset($data['password'])){
            $exam->password = $data['password'];
            $exam->save();
        }

        return (new ExamResource($exam))->response()->setStatusCode(200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Exam  $exam
     * @return \Illuminate\Http\Response
     */
    public function destroy(Exam $exam)
    {
        $exam->delete();
        return response(null, 202);
    }

    public function publish(Exam $exam, CanExamBePublished $action)
    {
        $this->authorize('publish', [$exam]);
        $status = $action->check($exam);
        if($status !== 'success'){
            return (new MessageResource([
                'message' => $status
                ]))->response()->setStatusCode(401);
        }

        $exam->published = true;
        $exam->save();

        return response(null, 202);
    }

    public function unpublish(Exam $exam)
    {
        $this->authorize('unpublish', $exam);
        $exam->published = false;
        $exam->save();

        return response(null, 202);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\QuestionType;
use Illuminate\Http\Request;

use App\Http\Resources\QuestionTypeResource;
use App\Http\Resources\QuestionTypeCollection;

class QuestionTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return (new QuestionTypeCollection(QuestionType::all()))->response()->setStatusCode(200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\QuestionType  $questionType
     * @return \Illuminate\Http\Response
     */
    public function show(QuestionType $questionType)
    {
        return (new QuestionTypeResource($questionType))->response()->setStatusCode(200);
    }

}

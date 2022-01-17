<?php

namespace App\Http\Controllers;

use App\Models\QuestionType;
use Illuminate\Http\Request;

use App\Http\Resources\QuestionTypeResource;
use App\Http\Resources\QuestionTypeCollection;

class QuestionTypeController extends Controller
{
    /**
     * index question types
     */
    public function index()
    {
        return (new QuestionTypeCollection(QuestionType::orderBy('id')->get()))->response()->setStatusCode(200);
    }

    /**
     * show a specific question type
     * @param  QuestionType $questionType
     */
    public function show(QuestionType $questionType)
    {
        return (new QuestionTypeResource($questionType))->response()->setStatusCode(200);
    }
}

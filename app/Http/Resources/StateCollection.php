<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

// use App\Actions\States\WhichStateColumnsMustBeSend;
use App\Actions\States\WhichStateColumnsMustBeSend;

class StateCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'states' => $this->reduce(function ($carry, $state) {
                $action = new WhichStateColumnsMustBeSend();
                $output = [];
                $columns = $action->check($state->question);
                if (in_array('id', $columns)) {
                    $output['state_id'] = $state->id;
                }
                if (in_array('text_answer', $columns)) {
                    $output['text_part'] = $state->text_answer;
                }
                if (in_array('integer_answer', $columns)) {
                    $output['integer_part'] = $state->integer_answer;
                }
                $output['question_id'] = $state->question_id;
                $output['question_link'] = route('questions.show', [$state->question->exam, $state->question]);
                return $carry->merge(collect([
                     $output
                ]));
            }, collect())
        ];
    }
}

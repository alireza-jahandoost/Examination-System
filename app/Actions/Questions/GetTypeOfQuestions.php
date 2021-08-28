<?php

namespace App\Actions\Questions;

use Illuminate\Support\Str;


class GetTypeOfQuestions
{
    /**
     * get a type id and return an array about that type
     *
     * @param  integer $type
     * @return array
     */
    public function get($type)
    {
        switch ($type) {
            case 1:
                $name = 'descriptive';
                return [
                    'id' => 1,
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'number_of_states' => 0,
                    'number_of_answers' => 1,
                    'type_of_answer' => 'text'
                ];
                break;
            case 2:
                $name = "fill the blank";
                return [
                    'id' => 2,
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'number_of_states' => 'multiple',
                    'number_of_answers' => 1,
                    'type_of_answer' => 'text'
                ];
                break;
            case 3:
                $name = "multiple answer";
                return [
                    'id' => 3,
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'number_of_states' => 'multiple',
                    'number_of_answers' => 'multiple',
                    'type_of_answer' => 'integer',
                ];
                break;
            case 4:
                $name = "select the answer";
                return [
                    'id' => 4,
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'number_of_states' => 'multiple',
                    'number_of_answers' => 1,
                    'type_of_answer' => 'integer',
                ];
                break;
            case 5:
                $name = "true or false";
                return [
                    'id' => 5,
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'number_of_states' => 1,
                    'number_of_answers' => 1,
                    'type_of_answer' => 'integer',
                ];
                break;
            case 6:
                $name = "ordering";
                return [
                    'id' => 6,
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'number_of_states' => 'multiple',
                    'number_of_answers' => 'multiple',
                    'type_of_answer' => 'integer',
                ];
                break;
            default:
                return "invalid";
                break;
        }
    }
    /**
     * get all the types
     *
     * @param  integer $type
     * @return array
     */
    public function getAll()
    {
        $output = [];
        for($i = 1; true ;$i ++){
            if($this->get($i) === "invalid"){
                break;
            }
            $output[] = $this->get($i);
        }

        return $output;
    }
}

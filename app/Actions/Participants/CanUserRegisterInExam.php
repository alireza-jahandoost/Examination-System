<?php

namespace App\Actions\Participants;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

use App\Models\Exam;

class CanUserRegisterInExam
{
    /**
     * get a type id and return an array about that type
     *
     * @param  integer $type
     * @return array
     */
     public function check(Exam $exam, $inputs)
     {
         if($exam->password){
             if(! isset($inputs['password'])){
                 return 'this exam needs password for registering';
             }else if(! Hash::check($inputs['password'], $exam->password)){
                 return 'password is not correct';
             }
         }

         return 'success';
     }
}

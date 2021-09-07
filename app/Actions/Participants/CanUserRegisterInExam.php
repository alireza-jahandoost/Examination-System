<?php

namespace App\Actions\Participants;

use Illuminate\Support\Facades\Hash;

use App\Models\Exam;

class CanUserRegisterInExam
{
    /**
     * check user can register in exam or not
     * @param  Exam   $exam
     * @param  array  $inputs
     * @return string
     */
    public function check(Exam $exam, array $inputs): string
    {
        if ($exam->password) {
            if (! isset($inputs['password'])) {
                return 'this exam needs password for registering';
            } elseif (! Hash::check($inputs['password'], $exam->password)) {
                return 'password is not correct';
            }
        }

        return 'success';
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use App\Actions\Fortify\PasswordValidationRules;

class PasswordResetRequest extends FormRequest
{
    use PasswordValidationRules;
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email' => 'required|string|email|max:255|exists:users,email',
            'password' => $this->passwordRules(),
            'token' => 'required|string|max:255',
        ];
    }
}

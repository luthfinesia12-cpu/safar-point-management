<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->can($this->isMethod('post') ? 'users.create' : 'users.update') ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('user')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$id],
            'password' => [$this->isMethod('post') ? 'required' : 'nullable', 'confirmed', Password::min(12)],
            'role' => ['required', 'exists:roles,name'],
        ];
    }
}

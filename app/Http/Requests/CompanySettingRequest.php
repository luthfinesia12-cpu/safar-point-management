<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompanySettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->can('settings.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'], 'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'], 'email' => ['nullable', 'email', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'], 'remove_logo' => ['boolean'],
        ];
    }
}

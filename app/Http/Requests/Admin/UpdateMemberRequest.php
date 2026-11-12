<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('username')) {
            $this->merge([
                'username' => strtolower(trim((string) $this->input('username'))),
            ]);
        }
    }

    public function rules(): array
    {
        $memberId = $this->route('member')->id;

        return [
            'name' => ['required', 'string', 'max:150'],
            'username' => [
                'required',
                'string',
                'alpha_dash',
                'min:3',
                'max:50',
                Rule::unique('users', 'username')->ignore($memberId),
            ],
            'division_id' => ['required', 'integer', 'exists:divisions,id'],
            'role' => ['required', Rule::enum(UserRole::class)],
        ];
    }
}

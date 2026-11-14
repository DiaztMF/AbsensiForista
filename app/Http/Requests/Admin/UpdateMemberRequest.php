<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
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

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $member = $this->route('member');
        $memberId = $member instanceof User ? $member->id : (int) $member;

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

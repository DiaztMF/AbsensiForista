<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ShiftSessionWindowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'window_opens_at' => ['required', 'date'],
            'window_closes_at' => ['required', 'date', 'after:window_opens_at'],
        ];
    }
}

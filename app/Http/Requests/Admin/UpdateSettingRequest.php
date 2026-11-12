<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'default_radius_m' => ['required', 'integer', 'min:5', 'max:100'],
            'default_window_minutes' => ['required', 'integer', 'min:5', 'max:120'],
            'gps_accuracy_ceiling_m' => ['required', 'integer', 'min:5', 'max:100'],
            'remind_before_start_minutes' => ['required', 'integer', 'min:0', 'max:180'],
            'remind_on_window_open' => ['required', 'boolean'],
            'remind_before_close_minutes' => ['required', 'integer', 'min:1', 'max:60'],
        ];
    }
}

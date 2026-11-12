<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

class CheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active === true;
    }

    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy_m' => ['required', 'numeric', 'min:0', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.required' => 'Koordinat lokasi tidak ditemukan. Pastikan GPS aktif.',
            'longitude.required' => 'Koordinat lokasi tidak ditemukan. Pastikan GPS aktif.',
            'accuracy_m.required' => 'Data akurasi GPS tidak valid.',
        ];
    }
}

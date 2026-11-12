<?php

namespace App\Actions\Attendance;

use App\Models\Attendance;

readonly class CheckInResult
{
    public function __construct(
        public bool $success,
        public string $code,
        public string $message,
        public ?Attendance $attendance = null,
        public ?float $distanceM = null,
        public ?int $remainingAttempts = null,
    ) {}

    public static function success(Attendance $attendance, string $message = 'Presensi berhasil!'): self
    {
        return new self(
            success: true,
            code: 'matched',
            message: $message,
            attendance: $attendance,
            distanceM: $attendance->distance_m,
        );
    }

    public static function rejected(
        string $code,
        string $message,
        ?float $distanceM = null,
        ?int $remainingAttempts = null
    ): self {
        return new self(
            success: false,
            code: $code,
            message: $message,
            distanceM: $distanceM,
            remainingAttempts: $remainingAttempts,
        );
    }
}

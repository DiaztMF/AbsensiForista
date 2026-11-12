<?php

namespace App\Enums;

enum AuditAction: string
{
    case ManualAttendance = 'manual_attendance';
    case PasswordReset = 'password_reset';
}
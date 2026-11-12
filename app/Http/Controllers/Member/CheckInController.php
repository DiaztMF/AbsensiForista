<?php

namespace App\Http\Controllers\Member;

use App\Actions\Attendance\ProcessCheckInAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Member\CheckInRequest;
use App\Models\AgendaSession;
use Illuminate\Http\RedirectResponse;

class CheckInController extends Controller
{
    public function __invoke(
        CheckInRequest $request,
        AgendaSession $session,
        ProcessCheckInAction $action
    ): RedirectResponse {
        $result = $action->execute(
            user: $request->user(),
            session: $session,
            userLat: (float) $request->validated('latitude'),
            userLng: (float) $request->validated('longitude'),
            accuracyM: (float) $request->validated('accuracy_m'),
        );

        return back()->with('checkin_result', [
            'success' => $result->success,
            'code' => $result->code,
            'message' => $result->message,
            'distance_m' => $result->distanceM,
            'remaining_attempts' => $result->remainingAttempts,
        ]);
    }
}

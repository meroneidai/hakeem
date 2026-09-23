<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\StaffAttendance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    use ResolvesClinicContext;

    public function index(Request $request): View
    {
        $clinic = $this->clinic($request);

        $shifts = StaffAttendance::query()
            ->with('user')
            ->where('clinic_id', $clinic->id)
            ->latest('clocked_in_at')
            ->paginate(40);

        return view('clinic.attendance.index', [
            'shifts' => $shifts,
            'openShift' => $request->user()->openAttendance(),
        ]);
    }

    public function clockIn(Request $request): RedirectResponse
    {
        if ($request->user()->openAttendance()) {
            throw ValidationException::withMessages([
                'shift' => __('admin.attendance.already_open'),
            ]);
        }

        StaffAttendance::query()->create([
            'user_id' => $request->user()->id,
            'clinic_id' => $this->clinic($request)->id,
            'clocked_in_at' => now(),
            'source' => 'web',
        ]);

        return back()->with('status', __('admin.attendance.clocked_in'));
    }

    public function clockOut(Request $request): RedirectResponse
    {
        $shift = $request->user()->openAttendance();

        if (! $shift) {
            throw ValidationException::withMessages([
                'shift' => __('admin.attendance.none_open'),
            ]);
        }

        $shift->clockOut();

        return back()->with('status', __('admin.attendance.clocked_out'));
    }
}

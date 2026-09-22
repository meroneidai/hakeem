<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\StaffAttendance;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $canManage = $request->user()->can(Permission::ManageAttendance->value);

        $shifts = StaffAttendance::query()
            ->with(['user', 'clinic'])
            ->when(! $canManage, fn ($query) => $query->where('user_id', $request->user()->id))
            ->latest('clocked_in_at')
            ->paginate(40);

        return view('admin.attendance.index', [
            'shifts' => $shifts,
            'openShift' => $request->user()->openAttendance(),
            'canManage' => $canManage,
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
            'clocked_in_at' => now(),
            'source' => 'web',
        ]);

        Audit::log('attendance.clock_in');

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
        Audit::log('attendance.clock_out');

        return back()->with('status', __('admin.attendance.clocked_out'));
    }
}

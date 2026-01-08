<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Attendance;
use Livewire\Attributes\Js;
use Livewire\Attributes\On;
use Illuminate\Support\Carbon;
use App\Models\AttendanceTimestamp;
use Illuminate\Support\Facades\Crypt;

class EmployeeAttendance extends Component
{
    public $forProject, $project, $clockedIn, $timeStarted;
    public $totalHours = 0;
    public $timeId = null;
    public $attendances, $todayActivity;

    public $totalHoursToday;
    public $totalHoursThisMonth;
    public $totalHoursThisWeek;

    public function clockin()
    {
        try {

            $user  = auth()->user();
            if ($this->forProject) {
                $this->validate([
                    'project' => 'required',
                ]);
            }
            $todayAttendance = Attendance::where('user_id', $user->id)
                ->whereDate('created_at', Carbon::today())->first();
            if (!empty($todayAttendance)) {
                $attendance = $todayAttendance;
            }

            AttendanceTimestamp::create([
                'user_id' => $user->id,
                'attendance_id' => $attendance->id,
                'project_id' => $this->project,
                'startTime' => now(),
                'endTime' => null,
                'location' => $user->employeeDetail->department->location ?? null,
                'billable' => false,
                'ip' => request()->ip() ?? null,
            ]);
            $this->dispatch('IsClockedIn');
            $this->dispatch('refreshAttendance');
            $this->dispatch('Notification', __('You have clockin successfully'));
            $this->js("bootstrap.Modal.getInstance(document.getElementById('clockin_modal')).hide()");
        } catch (\Exception $e) {
            $this->dispatch('Notification', __('Something went wrong'));
        }
    }

    public function clockout($timestampId)
    {
        try {
            $timestamp = AttendanceTimestamp::find(Crypt::decrypt($timestampId));
            $timestamp->attendance->update([
                'endDate' => now(),
            ]);
            $timestamp->update([
                'endTime' => now(),
            ]);
            $this->dispatch('IsClockedIn');
            $this->dispatch('refreshAttendance');
            $this->dispatch('Notification', __('You have clockout successfully'));
        } catch (\Exception $e) {
            $this->dispatch('Notification', __('Something went wrong'));
        }
    }

    private function calculateFromPunchesJson(string $punchesJson, string $date)
    {
        $punches = collect(json_decode($punchesJson ?? '[]', true))
            ->filter(
                fn($p) =>
                isset($p['device'], $p['punch_time']) &&
                    Carbon::parse($p['punch_time'])->toDateString() === $date
            )
            ->sortBy('punch_time')
            ->values();

        $totalMinutes = 0;
        $openIn = null;

        $firstIn = null;
        $lastOut = null;

        foreach ($punches as $punch) {
            $time = Carbon::parse($punch['punch_time']);

            if ($punch['device'] === 'IN_FLOOR') {
                if (!$firstIn) {
                    $firstIn = $punch;
                }
                if ($openIn === null) {
                    $openIn = $time;
                }
            }

            if ($punch['device'] === 'OUT_FLOOR' && $openIn !== null) {
                if ($time->greaterThan($openIn)) {
                    $totalMinutes += $time->diffInMinutes($openIn);
                    $lastOut = $punch;
                }
                $openIn = null;
            }
        }

        return [
            'firstIn'    => $firstIn,
            'lastOut'    => $lastOut,
            'totalHours' => round(max(0, $totalMinutes) / 60, 2),
            'punches'    => $punches,
        ];
    }

    #[On('refreshAttendance')]
    public function getAttendance()
    {
        $userId = auth()->user()->id;
        $this->getAttendances($userId);

        // 🔥 SET TODAY HOURS FOR DASHBOARD CIRCLE
        $today = Carbon::today()->toDateString();

        $todayAttendance = Attendance::where('user_id', $userId)
            ->whereDate('created_at', $today)
            ->first();

        if ($todayAttendance) {
            $calc = $this->calculateFromPunchesJson(
                $todayAttendance->punches,
                $today
            );

            $this->totalHours = $calc['totalHours'];
        } else {
            $this->totalHours = 0;
        }
    }


    public function getAttendances($userId)
    {
        $records = Attendance::where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->get();

        $final = [];

        foreach ($records as $attendance) {

            $date = $attendance->created_at->toDateString();

            $calc = $this->calculateFromPunchesJson(
                $attendance->punches,
                $date
            );

            $final[] = (object)[
                'date'       => $date,
                'startTime'  => $calc['firstIn']
                    ? Carbon::parse($calc['firstIn']['punch_time'])
                    : null,
                'endTime'    => $calc['lastOut']
                    ? Carbon::parse($calc['lastOut']['punch_time'])
                    : null,
                'totalHours' => $calc['totalHours'],
            ];
        }

        $this->attendances = $final;
    }


    // public function getAttendances($userId = null)
    // {
    //     $this->todayActivity = Attendance::select('punches')->where('user_id', $userId)->whereDate('date', Carbon::today())->get();
    //     // $this->attendances = Attendance::select('punches')->where('user_id', $userId)->get();  

    //     $rawAttendances = Attendance::where('user_id', $userId)
    //         ->orderBy('created_at', 'ASC')
    //         ->get();

    //     $finalAttendances = [];

    //     foreach ($rawAttendances as $record) {

    //         // Decode punches JSON
    //         $punches = json_decode($record->punches, true);

    //         if (!is_array($punches)) {
    //             $punches = [];
    //         }

    //         // Convert punch_time into Carbon object
    //         foreach ($punches as &$p) {
    //             $p['dt'] = \Carbon\Carbon::parse($p['punch_time']);
    //         }
    //         unset($p);

    //         // FIRST MAIN_DOOR = Punch In
    //         $punchIn = collect($punches)
    //             ->where('device', 'IN_FLOOR')
    //             ->sortBy('dt')         // ascending
    //             ->first();

    //         // LAST OUT_FLOOR = Punch Out
    //         $punchOut = collect($punches)
    //             ->where('device', 'OUT_FLOOR')
    //             ->sortByDesc('dt')     // descending
    //             ->first();

    //         // Prepare variables
    //         $startTime = $punchIn['dt'] ?? null;
    //         $endTime   = $punchOut['dt'] ?? null;

    //         // Total hours calculation
    //         $totalHours = null;
    //         if ($startTime && $endTime) {
    //             $totalHours = $endTime->diff($startTime)->format('%H:%I');
    //         }

    //         // Push final formatted data
    //         $finalAttendances[] = (object)[
    //             'date'       => $record->created_at->toDateString(),
    //             'startTime'  => $startTime,
    //             'endTime'    => $endTime,
    //             'totalHours' => $totalHours,
    //         ];
    //     }
    //     $this->attendances = $finalAttendances;
    // }

    // #[On('fetchStatistics')]
    // public function statistics()
    // {
    //     $userId = auth()->user()->id;
    //     $userAttendances = AttendanceTimestamp::where('user_id', $userId)
    //                     ->whereNotNull('attendance_id');
    //     $this->totalHoursToday = $userAttendances->whereDate('created_at', Carbon::today())
    //                     ->get()
    //                     ->sum('totalHours');
    //     $this->totalHoursThisMonth = $userAttendances->whereMonth('created_at', Carbon::now())
    //                     ->get()
    //                     ->sum('totalHours');
    //     $this->totalHoursThisWeek = $userAttendances
    //                     ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
    //                     ->get()
    //                     ->sum('totalHours');
    // }

    private function calculateTotalHours($timestamps)
    {
        $totalSeconds = 0;

        foreach ($timestamps as $ts) {
            $start = Carbon::parse($ts->startTime);
            $end   = $ts->endTime ? Carbon::parse($ts->endTime) : now();

            if ($end->greaterThan($start)) {
                $totalSeconds += $end->diffInSeconds($start);
            }
        }

        // Convert to hours (decimal, e.g. 7.50)
        return round($totalSeconds / 3600, 2);

        // OR return HH:MM format instead:
        // return gmdate('H:i', $totalSeconds);
    }


    // #[On('fetchStatistics')]
    // public function statistics()
    // {
    //     $userId = auth()->user()->id;

    //     $this->totalHoursToday = $this->calculateTotalHours(
    //         AttendanceTimestamp::where('user_id', $userId)
    //             ->whereDate('startTime', Carbon::today())
    //             ->get()
    //     );

    //     $this->totalHoursThisWeek = $this->calculateTotalHours(
    //         AttendanceTimestamp::where('user_id', $userId)
    //             ->whereBetween('startTime', [
    //                 Carbon::now()->startOfWeek(),
    //                 Carbon::now()->endOfWeek()
    //             ])
    //             ->get()
    //     );

    //     $this->totalHoursThisMonth = $this->calculateTotalHours(
    //         AttendanceTimestamp::where('user_id', $userId)
    //             ->whereMonth('startTime', Carbon::now()->month)
    //             ->whereYear('startTime', Carbon::now()->year)
    //             ->get()
    //     );
    // }

    #[On('fetchStatistics')]
    public function statistics()
    {
        $userId = auth()->user()->id;

        $records = Attendance::where('user_id', $userId)->get();

        $this->totalHoursToday = 0;
        $this->totalHoursThisWeek = 0;
        $this->totalHoursThisMonth = 0;

        foreach ($records as $attendance) {

            $date = $attendance->created_at->toDateString();
            $calc = $this->calculateFromPunchesJson(
                $attendance->punches,
                $date
            );

            if ($date === Carbon::today()->toDateString()) {
                $this->totalHoursToday += $calc['totalHours'];
            }

            if (Carbon::parse($date)->isCurrentWeek()) {
                $this->totalHoursThisWeek += $calc['totalHours'];
            }

            if (Carbon::parse($date)->isCurrentMonth()) {
                $this->totalHoursThisMonth += $calc['totalHours'];
            }
        }
    }


    #[On('IsClockedIn')]
    public function getClockInData()
    {
        $todayClockin = Attendance::where('user_id', auth()->user()->id)
            ->whereDate('created_at', Carbon::today())
            ->first();
        if (!empty($todayClockin)) {
            $latestClockin = $todayClockin->timestamps()->latest()->whereNull('endTime')->first() ?? null;
            if (!empty($latestClockin)) {
                $this->clockedIn = true;
                $this->timeId = Crypt::encrypt($latestClockin->id);
                $this->timeStarted = $latestClockin->startTime;
                $this->totalHours = Carbon::now()->diff($latestClockin->startTime)->h;
            }
        }
    }

    public function render()
    {
        return view('livewire.employee-attendance');
    }

    public function mount()
    {
        // $userId = auth()->user()->id;
        // $this->todayActivity = Attendance::select('punches')->where('user_id', $userId)->whereDate('date', Carbon::today())->get(); 
        // $this->attendances = $finalAttendances;
        // $this->todayActivity = Attendance::where('user_id', $userId)
        //     ->whereDate('created_at', Carbon::today())
        //     ->first();
        // $userId = auth()->user()->id;
        // $this->getAttendances($userId);

        $userId = auth()->user()->id;

        $this->todayActivity = Attendance::where('user_id', $userId)
            ->whereDate('created_at', Carbon::today())
            ->first();

        $this->getAttendances($userId);
    }
}

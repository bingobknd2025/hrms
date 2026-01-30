<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Attendance;
use Illuminate\Support\Carbon;

class EmployeeAttendance extends Component
{
    public array $attendances = [];
    public array $todayActivity = [];
    public bool $isManualUser = false;
    public bool $isClockedIn = false;

    public float $totalHours = 0;
    public float $totalHoursToday = 0;
    public float $totalHoursThisWeek = 0;
    public float $totalHoursThisMonth = 0;

    /**
     * =====================================
     * CORE CALCULATION (ADMIN-SAME LOGIC)
     * =====================================
     */
    // private function calculateFromPunchesJson(?string $punchesJson, string $date): array
    // {
    //     $punches = collect(json_decode($punchesJson ?? '[]', true))
    //         ->filter(
    //             fn($p) =>
    //             isset($p['device'], $p['punch_time']) &&
    //                 Carbon::parse($p['punch_time'])->toDateString() === $date
    //         )
    //         ->sortBy('punch_time')
    //         ->values();

    //     $firstMainDoor = $punches->where('device', 'MAIN_DOOR')->first();
    //     $lastOutFloor  = $punches->where('device', 'OUT_FLOOR')->last();

    //     if (!$firstMainDoor || !$lastOutFloor) {
    //         return [
    //             'punchIn'    => null,
    //             'punchOut'   => null,
    //             'totalHours' => 0,
    //             'punches'    => $punches->toArray(),
    //         ];
    //     }

    //     $start = Carbon::parse($firstMainDoor['punch_time']);
    //     $end   = Carbon::parse($lastOutFloor['punch_time']); 

    //     if ($end->lessThanOrEqualTo($start)) {
    //         return [
    //             'punchIn'    => null,
    //             'punchOut'   => null,
    //             'totalHours' => 0,
    //             'punches'    => $punches->toArray(),
    //         ];
    //     }

    //     return [
    //         'punchIn'    => $start,                 // 👈 IMPORTANT
    //         'punchOut'   => $end,                   // 👈 IMPORTANT
    //         'totalHours' => round($start->diffInSeconds($end) / 3600, 2),
    //         'punches'    => $punches->toArray(),
    //     ];
    // }
    // private function calculateFromPunchesJson(?string $punchesJson, string $date): array
    // {
    //     $punches = collect(json_decode($punchesJson ?? '[]', true))
    //         ->filter(
    //             fn($p) =>
    //             isset($p['device'], $p['punch_time']) &&
    //                 Carbon::parse($p['punch_time'])->toDateString() === $date
    //         )
    //         ->sortBy('punch_time')
    //         ->values();

    //     $firstMainDoor = $punches->where('device', 'MAIN_DOOR')->first();
    //     $lastOutFloor  = $punches->where('device', 'OUT_FLOOR')->last();

    //     // ALWAYS SHOW MAIN DOOR AS PUNCH IN
    //     $punchIn  = $firstMainDoor
    //         ? Carbon::parse($firstMainDoor['punch_time'])
    //         : null;

    //     $punchOut = $lastOutFloor
    //         ? Carbon::parse($lastOutFloor['punch_time'])
    //         : null;

    //     // Calculate hours ONLY if both exist
    //     $totalHours = 0;
    //     if ($punchIn && $punchOut && $punchOut->greaterThan($punchIn)) {
    //         // Interval nikalne ke liye
    //         $interval = $punchIn->diff($punchOut);

    //         // Total hours (days ko include karte hue taaki agar shift 24hr se zyada ho toh error na aaye)
    //         $hours = $interval->h + ($interval->days * 24);
    //         $minutes = $interval->i;

    //         // Display format
    //         $displayTime = $hours . " Hours, " . $minutes . " Mins";
    //         $totalHours = round(
    //             $punchIn->diffInSeconds($punchOut) / 3600,
    //             2
    //         );
    //     }

    //     return [
    //         'punchIn'    => $punchIn,
    //         'punchOut'   => $punchOut,
    //         'totalHours' => $totalHours,
    //         'punches'    => $punches->toArray(),
    //     ];
    // }

    private function calculateFromPunchesJson(?string $punchesJson, string $date): array
{
    $punches = collect(json_decode($punchesJson ?? '[]', true))
        ->filter(fn($p) =>
            isset($p['device'], $p['punch_time']) &&
            Carbon::parse($p['punch_time'])->toDateString() === $date
        )
        ->sortBy('punch_time')
        ->values();

    // Priority Punch In Logic
    $firstMainDoor = $punches->where('device', 'MAIN_DOOR')->first();
    $firstInFloor  = $punches->where('device', 'IN_FLOOR')->first();

    // If MAIN_DOOR exists use it, else fallback to IN_FLOOR
    $punchInData = $firstMainDoor ?? $firstInFloor;

    $lastOutFloor = $punches->where('device', 'OUT_FLOOR')->last();

    $punchIn  = $punchInData ? Carbon::parse($punchInData['punch_time']) : null;
    $punchOut = $lastOutFloor ? Carbon::parse($lastOutFloor['punch_time']) : null;

    // Calculate hours
    $totalHours = 0;
    $displayTime = null;

    if ($punchIn && $punchOut && $punchOut->greaterThan($punchIn)) {
        $interval = $punchIn->diff($punchOut);

        $hours   = $interval->h + ($interval->days * 24);
        $minutes = $interval->i;

        $displayTime = "{$hours} Hours, {$minutes} Mins";

        $totalHours = round($punchIn->diffInSeconds($punchOut) / 3600, 2);
    }

    return [
        'punchIn'      => $punchIn,
        'punchOut'     => $punchOut,
        'totalHours'   => $totalHours,
        'displayTime'  => $displayTime,
        'punches'       => $punches->toArray(),
    ];
}





    /**
     * =====================================
     * LOAD TABLE + TODAY HOURS
     * =====================================
     */
    public function loadAttendance(): void
    {
        $userId = auth()->id();

        $this->attendances = [];
        $this->totalHours = 0;

        $records = Attendance::where('user_id', $userId)
            ->orderBy('date', 'DESC')
            ->get();

        foreach ($records as $attendance) {
            $date = $attendance->date;

            $calc = $this->calculateFromPunchesJson(
                $attendance->punches,
                $date
            );

            $this->attendances[] = [
                'date'       => $date,
                'punchIn'    => $calc['punchIn'],
                'punchOut'   => $calc['punchOut'],
                'totalHours' => $calc['totalHours'],
            ];
            if ($date === now()->toDateString()) {
                $this->totalHours = $calc['totalHours'];
            }
        }
    }

    /**
     * =====================================
     * STATISTICS
     * =====================================
     */
    public function statistics(): void
    {
        $userId = auth()->id();

        $this->totalHoursToday = 0;
        $this->totalHoursThisWeek = 0;
        $this->totalHoursThisMonth = 0;

        $records = Attendance::where('user_id', $userId)->get();

        foreach ($records as $attendance) {
            $date = $attendance->date;

            $calc = $this->calculateFromPunchesJson(
                $attendance->punches,
                $date
            );

            if ($date === now()->toDateString()) {
                $this->totalHoursToday += $calc['totalHours'];
            }

            if (Carbon::parse($date)->isCurrentWeek()) {
                $this->totalHoursThisWeek += $calc['totalHours'];
            }

            if (Carbon::parse($date)->isCurrentMonth()) {
                $this->totalHoursThisMonth += $calc['totalHours'];
            }
        }

        $this->totalHoursToday = round($this->totalHoursToday, 2);
        $this->totalHoursThisWeek = round($this->totalHoursThisWeek, 2);
        $this->totalHoursThisMonth = round($this->totalHoursThisMonth, 2);
    }

    public function manualClockIn(): void
    {
        if (!$this->isManualUser) {
            return;
        }

        $userId = auth()->id();
        $date   = now()->toDateString(); //ALWAYS STRING

        //ALWAYS ensure date exists
        $attendance = Attendance::firstOrCreate(
            [
                'user_id' => $userId,
                'date'    => $date,
            ],
            [
                'punches' => json_encode([]),
            ]
        );

        $punches = json_decode($attendance->punches ?? '[]', true);

        //PREVENT DOUBLE CLOCK-IN
        $lastPunch = collect($punches)->last();
        if ($lastPunch && $lastPunch['device'] === 'MAIN_DOOR') {
            return;
        }

        //ADD MAIN_DOOR ENTRY
        $punches[] = [
            'device'     => 'MAIN_DOOR',
            'punch_time' => now()->toDateTimeString(),
        ];

        $attendance->update([
            'punches' => json_encode($punches),
        ]);

        $this->isClockedIn = true;

        $this->mount(); // refresh state
    }


    public function manualClockOut(): void
    {
        if (!$this->isManualUser) return;

        $userId = auth()->id();
        $date = now()->toDateString();

        $attendance = Attendance::where('user_id', $userId)
            ->where('date', $date)
            ->first();

        if (!$attendance) return;

        $punches = json_decode($attendance->punches, true) ?? [];

        $last = collect($punches)->last();
        if (!$last || $last['device'] !== 'MAIN_DOOR') return;

        $punches[] = [
            'device' => 'OUT_FLOOR',
            'punch_time' => now()->toDateTimeString(),
        ];

        $attendance->update([
            'punches' => json_encode($punches),
        ]);

        $this->isClockedIn = false;

        $this->mount(); // refresh data
    }



    /**
     * =====================================
     * INIT
     * =====================================
     */
    public function mount(): void
    {
        $user = auth()->user();
        $userId = $user->id;
        $today = Attendance::where('user_id', $userId)
            ->where('date', now()->toDateString())
            ->first();
        $this->isManualUser = strtolower($user->country) !== 'india';
        $this->todayActivity = $today
            ? collect(json_decode($today->punches ?? '[]', true))
            ->sortBy('punch_time')
            ->values()
            ->toArray()
            : [];

        if ($this->isManualUser && $today) {
            $lastPunch = collect($this->todayActivity)->last();
            $this->isClockedIn = $lastPunch && $lastPunch['device'] === 'MAIN_DOOR';
        }

        $this->loadAttendance();
        $this->statistics();
    }

    public function render()
    {
        return view('livewire.employee-attendance');
    }
}

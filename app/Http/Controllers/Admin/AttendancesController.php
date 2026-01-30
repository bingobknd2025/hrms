<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Enums\UserType;
use Carbon\CarbonPeriod;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;

class AttendancesController extends Controller
{

    // public function index(Request $request){

    //     $pageTitle = __('Attendances');

    //     $selectedMonth = $request->month ?? Carbon::now()->month;
    //     $selectedYear = $request->year ?? Carbon::now()->year;

    //     $years_range = CarbonPeriod::create(now()->subYears(10), Carbon::now()->addYears(10))->years();
    //     $days_in_month = Carbon::createFromDate($selectedYear, $selectedMonth,01)->daysInMonth;
    //     $users = User::with(['attendances' => function ($query) use ($selectedMonth,$selectedYear) {
    //         $query->whereMonth('created_at', $selectedMonth)
    //             ->whereYear('created_at', $selectedYear)
    //             ->orderBy('created_at', 'desc')
    //             ->take(1);
    //     }])->where('type', UserType::EMPLOYEE);
    //     if(!empty($request->employee)){
    //         $users = $users->where('email','LIKE','%'.$request->employee.'%')
    //                     ->orWhere('firstname','LIKE','%'.$request->employee.'%')
    //                     ->orWhere('middlename','LIKE','%'.$request->employee.'%')
    //                     ->orWhere('lastname','LIKE','%'.$request->employee.'%')
    //                     ->orWhere('username','LIKE','%'.$request->employee.'%');
    //     }
    //     $employees = $users->get();
    //     return view('pages.attendances.index',compact(
    //         'pageTitle','employees','years_range','days_in_month'
    //     ));
    // }

    public function index(Request $request)
    {
        $pageTitle = __('Attendances');

        $selectedMonth = $request->month ?? Carbon::now()->month;
        $selectedYear  = $request->year ?? Carbon::now()->year;

        $years_range = CarbonPeriod::create(now()->subYears(10), now()->addYears(10))->years();
        $days_in_month = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->daysInMonth;

        $users = User::where('type', UserType::EMPLOYEE);

        if ($request->employee) {
            $users = $users->where(function ($q) use ($request) {
                $q->where('email', 'LIKE', '%' . $request->employee . '%')
                    ->orWhere('firstname', 'LIKE', '%' . $request->employee . '%')
                    ->orWhere('lastname', 'LIKE', '%' . $request->employee . '%')
                    ->orWhere('username', 'LIKE', '%' . $request->employee . '%');
            });
        }

        $employees = $users->get();

        foreach ($employees as $emp) {

            $emp->daily = []; // IMPORTANT FIX

            for ($day = 1; $day <= $days_in_month; $day++) {

                // $attendance = Attendance::where('user_id', $emp->id)
                //     ->whereDay('created_at', $day)
                //     ->whereMonth('created_at', $selectedMonth)
                //     ->whereYear('created_at', $selectedYear)
                //     ->first();

                $attendance = Attendance::where('user_id', $emp->id)
                    ->whereDate('date', Carbon::create($selectedYear, $selectedMonth, $day))
                    ->first();

                $emp->daily[$day] = false; // default absent

                if ($attendance && $attendance->punches) {

                    $punches = json_decode($attendance->punches, true);

                    if (is_array($punches)) {
                        if (is_array($punches) && count($punches)) {
                            $emp->daily[$day] = $attendance->id;
                        }
                        // $firstMainDoor = collect($punches)
                        //     // ->where('type', 'MAIN_DOOR')
                        //     ->where('type', 'IN_FLOOR')
                        //     ->sortBy('punch_time')
                        //     ->first();

                        // // PRESENT LOGIC
                        // if (!empty($firstMainDoor)) {
                        //     $emp->daily[$day] = $attendance->id; // store attendance id for link
                        // }
                    }
                }
            }
        }

        return view('pages.attendances.index', compact(
            'pageTitle',
            'employees',
            'years_range',
            'days_in_month'
        ));
    }

    // public function attendanceDetails(Request $request, Attendance $attendance)
    // {
    //     // $attendanceActivity = $attendance->timestamps()->get();
    //     // $totalHours = $attendance->timestamps()->get()->sum('totalHours');
    //     // return view('pages.attendances.attendance-details', compact(
    //     //     'attendance',
    //     //     'totalHours',
    //     //     'attendanceActivity'
    //     // ));

    //     // $punches = collect(json_decode($attendance->punches ?? '[]', true))
    //     //     ->sortBy('punch_time')
    //     //     ->values();

    //     // $totalMinutes = 0;
    //     // $lastInTime = null;

    //     // $firstIn = null;
    //     // $lastOut = null;

    //     // foreach ($punches as $punch) {

    //     //     $time = Carbon::parse($punch['punch_time']);

    //     //     // Capture FIRST IN
    //     //     if ($punch['device'] === 'IN_FLOOR' && !$firstIn) {
    //     //         $firstIn = $punch;
    //     //     }

    //     //     if ($punch['device'] === 'IN_FLOOR') {

    //     //         // Open session only if none open
    //     //         if ($lastInTime === null) {
    //     //             $lastInTime = $time;
    //     //         }
    //     //     } elseif ($punch['device'] === 'OUT_FLOOR') {

    //     //         // Capture LAST OUT
    //     //         $lastOut = $punch;

    //     //         // Close session only if valid
    //     //         if ($lastInTime !== null && $time->greaterThan($lastInTime)) {
    //     //             $totalMinutes += $time->diffInMinutes($lastInTime);
    //     //             $lastInTime = null;
    //     //         }
    //     //     }
    //     // }

    //     // // Convert minutes → hours (SAFE)
    //     // $totalHours = round($totalMinutes / 60, 2);
    //     // $totalHours = max(0, $totalHours);

    //     $attendanceDate = Carbon::parse(
    //         $attendance->date
    //     )->toDateString();

    //     // Decode & FILTER punches by SAME DATE
    //     $punches = collect(json_decode($attendance->punches ?? '[]', true))
    //         ->filter(function ($punch) use ($attendanceDate) {
    //             return Carbon::parse($punch['punch_time'])->toDateString() === $attendanceDate;
    //         })
    //         ->sortBy('punch_time')
    //         ->values();

    //     $totalMinutes = 0;
    //     $openInTime = null;

    //     $firstIn = null;
    //     $lastOut = null;

    //     foreach ($punches as $punch) {

    //         $time = Carbon::parse($punch['punch_time']);

    //         if ($punch['device'] === 'IN_FLOOR') {

    //             if (!$firstIn) {
    //                 $firstIn = $punch;
    //             }

    //             // Open only if no session open
    //             if ($openInTime === null) {
    //                 $openInTime = $time;
    //             }
    //         }

    //         if ($punch['device'] === 'OUT_FLOOR' && $openInTime !== null) {

    //             // SAFETY CHECK — NEVER allow reverse diff
    //             if ($time->greaterThan($openInTime)) {
    //                 $totalMinutes += $openInTime->diffInMinutes($time);
    //                 $lastOut = $punch;
    //             }

    //             // Close session
    //             $openInTime = null;
    //         }
    //     }

    //     // FINAL GUARANTEE
    //     $totalMinutes = max(0, $totalMinutes);
    //     $totalHours = round($totalMinutes / 60, 2);

    //     return view('pages.attendances.attendance-details', [
    //         'attendance' => $attendance,
    //         'punches'    => $punches,
    //         'firstIn'    => $firstIn,
    //         'lastOut'    => $lastOut,
    //         'totalHours' => $totalHours,
    //     ]);
    // }

    public function attendanceDetails(Request $request, Attendance $attendance)
    {
        $date = Carbon::parse($attendance->date)->toDateString();

        $calc = $this->calculateMainDoorToLastOut(
            $attendance->punches,
            $date
        );

        return view('pages.attendances.attendance-details', [
            'attendance'     => $attendance,
            'punches'        => $calc['punches'],
            'firstMainDoor'  => $calc['firstMainDoor'],
            'lastOutFloor'   => $calc['lastOutFloor'],
            'totalHours'     => $calc['totalHours'],
        ]);
    }

    private function calculateMainDoorToLastOut(?string $punchesJson, string $date): array
    {
        $punches = collect(json_decode($punchesJson ?? '[]', true))
            ->filter(
                fn($p) =>
                isset($p['device'], $p['punch_time']) &&
                    Carbon::parse($p['punch_time'])->toDateString() === $date
            )
            ->sortBy('punch_time')
            ->values();

        //FIRST MAIN_DOOR
        $firstMainDoor = $punches
            ->where('device', 'MAIN_DOOR')
            ->first();

        //LAST OUT_FLOOR
        $lastOutFloor = $punches
            ->where('device', 'OUT_FLOOR')
            ->last();

        if (!$firstMainDoor || !$lastOutFloor) {
            return [
                'firstMainDoor' => null,
                'lastOutFloor'  => null,
                'totalHours'    => 0,
                'punches'       => $punches,
            ];
        }

        $start = Carbon::parse($firstMainDoor['punch_time']);
        $end   = Carbon::parse($lastOutFloor['punch_time']);

        //SAFETY CHECK
        if ($end->lessThanOrEqualTo($start)) {
            return [
                'firstMainDoor' => $firstMainDoor,
                'lastOutFloor'  => $lastOutFloor,
                'totalHours'    => 0,
                'punches'       => $punches,
            ];
        }

        return [
            'firstMainDoor' => $firstMainDoor,
            'lastOutFloor'  => $lastOutFloor,
            'totalHours'    => round($start->diffInSeconds($end) / 3600, 2),
            'punches'       => $punches,
        ];
    }
}

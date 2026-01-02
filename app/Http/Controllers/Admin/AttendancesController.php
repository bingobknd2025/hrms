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

                $attendance = Attendance::where('user_id', $emp->id)
                    ->whereDay('created_at', $day)
                    ->whereMonth('created_at', $selectedMonth)
                    ->whereYear('created_at', $selectedYear)
                    ->first();

                $emp->daily[$day] = false; // default absent

                if ($attendance && $attendance->punches) {

                    $punches = json_decode($attendance->punches, true);

                    if (is_array($punches)) {

                        $firstMainDoor = collect($punches)
                            ->where('type', 'MAIN_DOOR')
                            ->sortBy('punch_time')
                            ->first();

                        // PRESENT LOGIC
                        if (!empty($firstMainDoor)) {
                            $emp->daily[$day] = $attendance->id; // store attendance id for link
                        }
                    }
                }
            }
        }

        return view('pages.attendances.index', compact(
            'pageTitle', 'employees', 'years_range', 'days_in_month'
        ));
    }

    public function attendanceDetails(Request $request, Attendance $attendance)
    {
        $attendanceActivity = $attendance->timestamps()->get();
        $totalHours = $attendance->timestamps()->get()->sum('totalHours');
        return view('pages.attendances.attendance-details',compact(
            'attendance','totalHours','attendanceActivity'
        ));
    }
}

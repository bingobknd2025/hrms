@php
    use Carbon\Carbon;
@endphp

<div class="modal-body">
    <div class="row">

        {{-- LEFT : SUMMARY --}}
        <div class="col-md-6">
            <div class="card punch-status">
                <div class="card-body">

                    <h5 class="card-title">
                        {{ __('Timesheet') }}
                        <small class="text-muted">
                            {{ $attendance->attendance_date ?? $attendance->created_at->toDateString() }}
                        </small>
                    </h5>

                    {{-- First Punch In --}}
                    @if ($firstIn)
                        <div class="punch-det">
                            <h6>{{ __('First Punch In At') }}</h6>
                            <p>{{ Carbon::parse($firstIn['punch_time'])->format('Y-m-d h:i A') }}</p>
                        </div>
                    @endif

                    {{-- Total Hours --}}
                    <div class="punch-info">
                        <div class="punch-hours">
                            <span>
                                {{ $totalHours }}
                                {{ \Str::plural(__('Hour'), (int) $totalHours) }}
                            </span>
                        </div>
                    </div>

                    {{-- Last Punch Out --}}
                    @if ($lastOut)
                        <div class="punch-det">
                            <h6>{{ __('Last Punch Out At') }}</h6>
                            <p>{{ Carbon::parse($lastOut['punch_time'])->format('Y-m-d h:i A') }}</p>
                        </div>
                    @endif

                </div>
            </div>
        </div>

        {{-- RIGHT : ACTIVITY --}}
        <div class="col-md-6">
            <div class="card recent-activity">
                <div class="card-body">

                    <h5 class="card-title">{{ __('Activity') }}</h5>

                    <ul class="res-activity-list">
                        @forelse ($punches as $punch)
                            <li>
                                <p class="mb-0">
                                    {{ $punch['device'] === 'IN_FLOOR' ? __('Punch In') : __('Punch Out') }}
                                </p>
                                <p class="res-activity-time">
                                    <i class="fa-regular fa-clock"></i>
                                    {{ Carbon::parse($punch['punch_time'])->format('h:i A') }}
                                </p>
                            </li>
                        @empty
                            <li class="text-muted">{{ __('No punch activity found') }}</li>
                        @endforelse
                    </ul>

                </div>
            </div>
        </div>

    </div>
</div>

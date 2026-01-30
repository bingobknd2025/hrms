@php use Carbon\Carbon; @endphp

<div class="modal-body">
    <div class="row">

        {{-- LEFT : SUMMARY --}}
        <div class="col-md-6">
            <div class="card punch-status">
                <div class="card-body">

                    <h5 class="card-title">
                        {{ __('Timesheet') }}
                        <small class="text-muted">{{ $attendance->date }}</small>
                    </h5>

                    @if ($firstMainDoor)
                        <div class="punch-det">
                            <h6>{{ __('First Entry (Main Door)') }}</h6>
                            <p>{{ Carbon::parse($firstMainDoor['punch_time'])->format('h:i A') }}</p>
                        </div>
                    @endif

                    <div class="punch-info">
                        <div class="punch-hours">
                            <span>
                                {{ $totalHours }}
                                {{ \Str::plural(__('Hour'), (int) $totalHours) }}
                            </span>
                        </div>
                    </div>

                    @if ($lastOutFloor)
                        <div class="punch-det">
                            <h6>{{ __('Last Exit (Out Floor)') }}</h6>
                            <p>{{ Carbon::parse($lastOutFloor['punch_time'])->format('h:i A') }}</p>
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
                        @foreach ($punches as $punch)
                            <li>
                                <p class="mb-0">{{ $punch['device'] }}</p>
                                <p class="res-activity-time">
                                    <i class="fa-regular fa-clock"></i>
                                    {{ Carbon::parse($punch['punch_time'])->format('h:i A') }}
                                </p>
                            </li>
                        @endforeach
                    </ul>

                </div>
            </div>
        </div>

    </div>
</div>

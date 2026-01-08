<div>
    <div class="row">

        {{-- ================= LEFT : TODAY SUMMARY ================= --}}
        <div class="col-md-4">
            <div class="card punch-status">
                <div class="card-body">

                    <h5 class="card-title">
                        <div class="row">
                            <div class="col-9">
                                {{ __('Timesheet') }}
                                <small class="text-muted">{{ format_date(date('Y-m-d')) }}</small>
                            </div>
                            <div class="col-3">
                                <div class="d-flex" x-data="{
                                    init() {
                                        setInterval(() => {
                                            const d = new Date();
                                            document.getElementById('spanTimer').innerText =
                                                d.getHours() + ':' + d.getMinutes() + ':' + d.getSeconds();
                                        }, 1000);
                                    }
                                }">
                                    <span id="spanTimer"></span>
                                </div>
                            </div>
                        </div>
                    </h5>

                    <div class="punch-info">
                        <div class="punch-hours">
                            <span>
                                {{ $totalHours }}
                                {{ \Str::plural(__('Hour'), $totalHours == 1 ? 1 : 2) }}
                            </span>
                        </div>
                    </div>

                    {{-- Clock buttons (you said you’ll remove later – kept safe) --}}
                    <div class="punch-btn-section">
                        @if (!empty($clockedIn) && !empty($timeId))
                            <button type="button" wire:click="clockout('{{ $timeId }}')"
                                class="btn btn-primary punch-btn">
                                {{ __('Clock Out') }}
                            </button>
                        @else
                            <button type="button" data-bs-toggle="modal" data-bs-target="#clockin_modal"
                                class="btn btn-primary punch-btn">
                                {{ __('Clock In') }}
                            </button>
                        @endif
                    </div>

                    @if (!empty($clockedIn) && !empty($timeStarted))
                        <div class="statistics mt-3">
                            <div class="stats-box text-center">
                                <p>{{ __('Started At') }}</p>
                                <h6>{{ format_date($timeStarted, 'h:i:s A') }}</h6>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>

        {{-- ================= MIDDLE : STATISTICS ================= --}}
        <div class="col-md-4">
            <div class="card att-statistics">
                <div class="card-body">

                    <h5 class="card-title">{{ __('Statistics') }}</h5>

                    <div class="stats-list">

                        <div class="stats-info">
                            <p>
                                {{ __('Today') }}
                                <strong>
                                    <small>{{ $totalHoursToday }}
                                        {{ \Str::plural(__('Hour'), $totalHoursToday) }}</small>
                                </strong>
                            </p>
                        </div>

                        <div class="stats-info">
                            <p>
                                {{ __('This Week') }}
                                <strong>
                                    <small>{{ $totalHoursThisWeek }}
                                        {{ \Str::plural(__('Hour'), $totalHoursThisWeek) }}</small>
                                </strong>
                            </p>
                        </div>

                        <div class="stats-info">
                            <p>
                                {{ __('This Month') }}
                                <strong>
                                    <small>{{ $totalHoursThisMonth }}
                                        {{ \Str::plural(__('Hour'), $totalHoursThisMonth) }}</small>
                                </strong>
                            </p>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        {{-- ================= RIGHT : TODAY ACTIVITY ================= --}}
        <div class="col-md-4">
            <div class="card recent-activity">
                <div class="card-body">

                    <h5 class="card-title">{{ __('Today Activity') }}</h5>

                    <ul class="res-activity-list">
                        @php
                            use Carbon\Carbon;

                            $punches = json_decode($todayActivity->punches ?? '[]', true);
                            if (!is_array($punches)) {
                                $punches = [];
                            }
                        @endphp

                        @forelse ($punches as $item)
                            <li>
                                <p class="mb-0">
                                    {{ ($item['device'] ?? '') === 'IN_FLOOR' ? __('Punch In') : __('Punch Out') }}
                                </p>
                                <p class="res-activity-time">
                                    <i class="fa-regular fa-clock"></i>
                                    {{ isset($item['punch_time']) ? Carbon::parse($item['punch_time'])->format('h:i:s A') : '-' }}
                                </p>
                            </li>
                            <hr>
                        @empty
                            <li class="text-muted">{{ __('No activity today') }}</li>
                        @endforelse
                    </ul>

                </div>
            </div>
        </div>
    </div>

    {{-- ================= TABLE : HISTORY ================= --}}
    <div class="row mt-3">
        <div class="col-lg-12">
            <div class="table-responsive">
                <table class="table table-striped custom-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Punch In') }}</th>
                            <th>{{ __('Punch Out') }}</th>
                            <th>{{ __('Total Hours') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($attendances as $i => $attendance)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $attendance->date }}</td>
                                <td>{{ $attendance->startTime ? $attendance->startTime->format('h:i A') : '-' }}</td>
                                <td>{{ $attendance->endTime ? $attendance->endTime->format('h:i A') : '-' }}</td>
                                <td>{{ $attendance->totalHours }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ================= LIVEWIRE EVENTS ================= --}}
    @script
        <script>
            document.addEventListener('livewire:initialized', () => {
                Livewire.dispatch('refreshAttendance');
                Livewire.dispatch('fetchStatistics');
                Livewire.dispatch('IsClockedIn');
            });

            Livewire.on('Notification', (msg) => {
                Toastify({
                    text: msg,
                    className: "success",
                }).showToast();
            });
        </script>
    @endscript
</div>

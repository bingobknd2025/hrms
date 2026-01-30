<div>
    <div class="row">
        <div class="col-md-4">
            <div class="card punch-status">
                <div class="card-body">
                    <h5 class="card-title">
                        <div class="row">
                            <div class="col-9">
                                {{ __('Timesheet') }} <small class="text-muted">{{ format_date(Date('Y-m-d')) }}</small>
                            </div>
                            <div class="col-3">
                                <div class="d-flex" x-data="{
                                    init() {
                                        function time() {
                                            var span = document.getElementById('spanTimer');
                                            var d = new Date();
                                            var s = d.getSeconds();
                                            var m = d.getMinutes();
                                            var h = d.getHours();
                                            span.textContent = h + ':' + m + ':' + s;
                                        }
                                        setInterval(time, 1000);
                                    }
                                }">
                                    <span id="spanTimer"></span>
                                </div>
                            </div>
                        </div>
                    </h5>

                    <div class="punch-info">
                        <div class="punch-hours">
                            <span>{{ $totalHours }} {{ \Str::plural(__('Hour'), intval($totalHours)) }}</span>
                        </div>
                    </div>
                    <div class="punch-btn-section">

                        {{-- MANUAL USERS (NON INDIA) --}}
                        @if ($isManualUser)
                            @if ($isClockedIn)
                                <button wire:click="manualClockOut" class="btn btn-danger punch-btn">
                                    {{ __('Clock Out') }}
                                </button>
                            @else
                                <button wire:click="manualClockIn" class="btn btn-primary punch-btn">
                                    {{ __('Clock In') }}
                                </button>
                            @endif

                            {{-- INDIA USERS (BIOMETRIC) --}}
                        @else
                            <span class="text-muted">
                                {{ __('Attendance is recorded automatically') }}
                            </span>
                        @endif

                    </div>

                    {{-- <div class="punch-btn-section">
                        @if (!empty($clockedIn) && !empty($timeId))
                            <button type="button" wire:click="clockout('{{ $timeId }}')"
                                class="btn btn-primary punch-btn">{{ __('Clock Out') }}</button>
                        @else
                            <button type="button" data-bs-toggle="modal" data-bs-target="#clockin_modal"
                                class="btn btn-primary punch-btn">{{ __('Clock In') }}</button>
                        @endif
                    </div> --}}
                    <div class="statistics">
                        <div class="row">
                            @if (!empty($clockedIn) && !empty($timeStarted))
                                <div class="col-md-12 text-center">
                                    <div class="stats-box">
                                        <p>{{ __('Started At') }}</p>
                                        <h6>{{ format_date($timeStarted, 'H:i:s A') }}</h6>
                                    </div>
                                </div>
                            @endif

                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card att-statistics">
                <div class="card-body">
                    <h5 class="card-title">{{ __('Statistics') }}</h5>
                    <div class="stats-list">
                        <div class="stats-info">
                            <p>{{ __('Today') }} <strong><small> {{ $totalHoursToday }}
                                        {{ \Str::plural(__('Hour'), $totalHoursToday) }}</small></strong></p>
                            <div class="progress">
                                <div class="progress-bar bg-primary w-{{ $totalHoursToday / 100 }}" role="progressbar"
                                    aria-valuenow="{{ $totalHoursToday / 100 }}" aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                        </div>
                        <div class="stats-info">
                            <p>{{ __('This Week ') }}<strong> <small> {{ $totalHoursThisWeek }}
                                        {{ \Str::plural(__('Hour'), $totalHoursThisWeek) }}</small></strong></p>
                            <div class="progress">
                                <div class="progress-bar bg-warning w-{{ $totalHoursThisWeek / 100 }}"
                                    role="progressbar" aria-valuenow="{{ $totalHoursThisWeek / 100 }}"
                                    aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                        <div class="stats-info">
                            <p>{{ __('This Month') }} <strong> <small> {{ $totalHoursThisMonth }}
                                        {{ \Str::plural(__('Hour'), $totalHoursToday) }}</small></strong></p>
                            <div class="progress">
                                <div class="progress-bar bg-success w-{{ $totalHoursThisMonth / 100 }}"
                                    role="progressbar" aria-valuenow="{{ $totalHoursThisMonth / 100 }}"
                                    aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card recent-activity">
                <div class="card-body">
                    <h5 class="card-title">{{ __('Today Activity') }}</h5>
                    <ul class="res-activity-list">
                        @forelse ($todayActivity as $item)
                            <li>
                                <p class="mb-0">
                                    {{ str_replace('_', ' ', $item['device']) }}
                                </p>
                                <p class="res-activity-time">
                                    <i class="fa-regular fa-clock"></i>
                                    {{ \Carbon\Carbon::parse($item['punch_time'])->format('h:i:s A') }}
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


    <div class="row">
        <div class="col-lg-12">
            <div class="table-responsive">
                <table class="table table-striped custom-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('Date') }} </th>
                            <th>{{ __('Punch In') }}</th>
                            <th>{{ __('Punch Out') }}</th>
                            <th>{{ __('Total Hours') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($attendances as $i => $attendance)
                            <tr>
                                <td>{{ ++$i }}</td>
                                <td>{{ $attendance['date'] }}</td>
                                <td> {{ $attendance['punchIn'] ? \Carbon\Carbon::parse($attendance['punchIn'])->format('h:i A') : '-' }}
                                </td>
                                <td>{{ $attendance['punchOut'] ? \Carbon\Carbon::parse($attendance['punchOut'])->format('h:i A') : '-' }}
                                </td>
                                <td>
    @if($attendance['totalHours'])
        @php
            $total = $attendance['totalHours'];
            $hours = floor($total); // Poore ghante nikalne ke liye (e.g. 10)
            $minutes = round(($total - $hours) * 60); // Bacha hua decimal ko 60 se multiply kiya (e.g. 0.84 * 60)
            
            // Agar minutes 60 ho jayein toh unhe 1 hour mein convert karne ke liye
            if($minutes == 60) {
                $hours++;
                $minutes = 0;
            }
        @endphp

        {{ $hours }} Hours, {{ $minutes }} Mins
    @else
        0 Hours, 0 Mins
    @endif
</td>
                            </tr>
                        @endforeach
                    </tbody>

                </table>
            </div>
        </div>
    </div>

    <div class="modal custom-modal fade" id="clockin_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="clockin" method="post" enctype="multipart/form-data">
                        @csrf
                        <div x-data="{ forProject: false }">
                            <x-form.input-block>
                                <div class="status-toggle">
                                    <x-form.label>{{ __('For Project ?') }}</x-form.label>
                                    <x-form.input type="checkbox" id="forProject" class="check"
                                        @click="forProject =! forProject" name="forProject" wire:model="forProject" />
                                    <label for="forProject" class="checktoggle">checkbox</label>
                                </div>
                            </x-form.input-block>
                            <div x-show="forProject">
                                <x-form.input-block>
                                    <x-form.label required>{{ __('Project') }}</x-form.label>
                                    <select class="form-control" name="project" wire:model="project">
                                        <option value="">{{ __('Select Project') }}</option>
                                        @foreach (\Modules\Project\Models\Project::get() as $project)
                                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                                        @endforeach
                                    </select>
                                </x-form.input-block>
                            </div>
                        </div>
                        <div class="submit-section mb-3">
                            <x-form.button class="btn btn-primary submit-btn">{{ __('Start') }}</x-form.button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @script
        <script defer type="module">
            document.addEventListener('livewire:initialized', () => {
                Livewire.dispatch('refreshAttendance')
                Livewire.dispatch('fetchStatistics')
                Livewire.dispatch('IsClockedIn')
            })

            Livewire.on('Notification', (param) => {
                Toastify({
                    text: param,
                    className: "success",
                }).showToast()
            })
        </script>
    @endscript
</div>

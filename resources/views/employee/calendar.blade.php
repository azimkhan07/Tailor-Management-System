@extends('layouts.app')

@push('page-css')
<style>
    table.cal { width:100%; table-layout:fixed; border-collapse:collapse; }
    table.cal th { text-align:center; padding:8px 4px; background:#f5f5f5; font-size:12px; text-transform:uppercase; letter-spacing:.5px; }
    table.cal td { border:1px solid #e3e3e3; height:96px; vertical-align:top; padding:5px; font-size:12px; }
    table.cal td.empty { background:#fafafa; }
    table.cal td.today { box-shadow: inset 0 0 0 2px #29b6f6; }
    table.cal td.closed { background:#fdecea; }
    table.cal .num { font-weight:600; }
    table.cal .hol { display:block; color:#c0392b; font-size:11px; margin-top:2px; }
    table.cal .lv { display:block; border-radius:3px; padding:1px 4px; margin-top:2px; color:#fff; font-size:10px; }
    table.cal .lv-pending { background:#f0ad4e; }
    table.cal .lv-approved { background:#7bb13c; }
    table.cal .lv-rejected { background:#d9534f; }
    table.cal td { cursor:pointer; }
    .legend span { display:inline-block; margin-right:14px; font-size:12px; }
    .legend i { display:inline-block; width:12px; height:12px; border-radius:3px; margin-right:4px; vertical-align:-1px; }
</style>
@endpush

@push('breadcrumb')
<h3 class="content-header-title">Calendar</h3>
<div class="row breadcrumbs-top">
	<div class="breadcrumb-wrapper col-12">
		<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="{{route('dashboard')}}">Home</a></li>
			<li class="breadcrumb-item active">Calendar</li>
		</ol>
	</div>
</div>
@endpush

@push('breadcrumb-button')
<button class="btn btn-outline-primary" data-toggle="modal" data-target="#apply-leave">Apply for leave</button>
@endpush

@section('content')
<div class="row">
    <div class="col-lg-8 col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <a href="{{ route('my-calendar', ['month' => $prev]) }}" class="btn btn-sm btn-outline-secondary"><i class="la la-angle-left"></i></a>
                <h4 class="card-title mb-0">{{ $month->format('F Y') }}</h4>
                <a href="{{ route('my-calendar', ['month' => $next]) }}" class="btn btn-sm btn-outline-secondary"><i class="la la-angle-right"></i></a>
            </div>
            <div class="card-content collapse show">
                <div class="card-body">
                    @php
                        $cells = array_fill(0, $leadingBlanks, null);
                        foreach ($days as $d) { $cells[] = $d; }
                        while (count($cells) % 7 !== 0) { $cells[] = null; }
                        $weeks = array_chunk($cells, 7);
                    @endphp
                    <div class="table-responsive">
                        <table class="cal">
                            <thead>
                                <tr>
                                    @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $wd)
                                        <th>{{ $wd }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($weeks as $week)
                                    <tr>
                                        @foreach($week as $day)
                                            @if($day === null)
                                                <td class="empty"></td>
                                            @else
                                                <td class="cal-day @if($day['isToday']) today @endif @if($day['holiday']) closed @endif"
                                                    data-date="{{ $day['date'] }}">
                                                    <span class="num">{{ $day['day'] }}</span>
                                                    @if($day['holiday'])<span class="hol">{{ $day['holiday'] }}</span>@endif
                                                    @foreach($day['leaves'] as $lv)
                                                        <span class="lv lv-{{ $lv['status'] }}">{{ $lv['name'] }}</span>
                                                    @endforeach
                                                </td>
                                            @endif
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="legend mt-2">
                        <span><i style="background:#fdecea"></i>Shop closed</span>
                        <span><i style="background:#f0ad4e"></i>Leave pending</span>
                        <span><i style="background:#7bb13c"></i>Leave approved</span>
                        <span><i style="background:#d9534f"></i>Leave rejected</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-12">
        @if($isAdmin)
            <div class="card">
                <div class="card-header"><h4 class="card-title mb-0">Leave requests</h4></div>
                <div class="card-content collapse show">
                    <div class="card-body">
                        @forelse($pendingLeaves as $leave)
                            <div class="border rounded p-2 mb-2">
                                <strong>{{ $leave->user->name }}</strong>
                                <div class="text-muted small">{{ $leave->start_date->format('d M') }} → {{ $leave->end_date->format('d M Y') }} ({{ $leave->day_count }}d)</div>
                                @if($leave->reason)<div class="small">{{ $leave->reason }}</div>@endif
                                <div class="d-flex mt-2" style="gap:6px">
                                    <form method="post" action="{{ route('leaves.decide', $leave) }}">
                                        @csrf
                                        <input type="hidden" name="status" value="approved">
                                        <button class="btn btn-sm btn-success">Approve</button>
                                    </form>
                                    <form method="post" action="{{ route('leaves.decide', $leave) }}">
                                        @csrf
                                        <input type="hidden" name="status" value="rejected">
                                        <button class="btn btn-sm btn-danger">Reject</button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted mb-0">No pending leave requests.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h4 class="card-title mb-0">Shop closed days</h4></div>
                <div class="card-content collapse show">
                    <div class="card-body">
                        <form method="post" action="{{ route('holidays.store') }}" class="mb-3">
                            @csrf
                            <input type="text" name="title" class="form-control mb-2" placeholder="Reason (e.g. Eid)" required>
                            <input type="date" name="date" class="form-control mb-2">
                            <select name="weekday" class="form-control mb-2">
                                <option value="">— or pick a weekly closed day —</option>
                                @foreach(['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $i => $name)
                                    <option value="{{ $i }}">Every {{ $name }}</option>
                                @endforeach
                            </select>
                            <button class="btn btn-primary btn-block">Add closed day</button>
                        </form>
                        <ul class="list-unstyled mb-0">
                            @forelse($holidays as $holiday)
                                <li class="d-flex justify-content-between align-items-center border-bottom py-1">
                                    <span>{{ $holiday->label }}</span>
                                    <form method="post" action="{{ route('holidays.destroy', $holiday) }}">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-link text-danger p-0">remove</button>
                                    </form>
                                </li>
                            @empty
                                <li class="text-muted">No closed days configured.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">My leaves</h4></div>
            <div class="card-content collapse show">
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        @forelse($myLeaves as $leave)
                            <li class="border-bottom py-1">
                                {{ $leave->start_date->format('d M') }} → {{ $leave->end_date->format('d M Y') }}
                                <span class="badge badge-{{ ['pending'=>'warning','approved'=>'success','rejected'=>'danger'][$leave->status] ?? 'secondary' }}">{{ $leave->status }}</span>
                            </li>
                        @empty
                            <li class="text-muted">You have not applied for any leave.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- apply leave modal -->
<div class="modal fade text-left" id="apply-leave" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <label class="modal-title">Apply for leave</label>
                <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <form method="post" action="{{ route('leaves.store') }}">
                @csrf
                <div class="modal-body">
                    <label>From</label>
                    <div class="form-group"><input type="date" name="start_date" id="leave-start" class="form-control" required></div>
                    <label>To</label>
                    <div class="form-group"><input type="date" name="end_date" id="leave-end" class="form-control" required></div>
                    <label>Reason</label>
                    <div class="form-group"><textarea name="reason" class="form-control" rows="3" maxlength="200" placeholder="Reason (optional)"></textarea></div>
                </div>
                <div class="modal-footer">
                    <input type="reset" class="btn btn-outline-secondary" data-dismiss="modal" value="close">
                    <button class="btn btn-primary" type="submit">Submit request</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('page-js')
<script>
$(function () {
    // Clicking a day opens the leave modal with that date pre-filled.
    $('.cal-day').on('click', function () {
        var date = $(this).data('date');
        $('#leave-start').val(date);
        $('#leave-end').val(date);
        $('#apply-leave').modal('show');
    });
});
</script>
@endpush

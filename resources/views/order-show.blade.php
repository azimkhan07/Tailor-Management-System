@extends('layouts.app')

@push('page-css')
<link rel="stylesheet" type="text/css" href="{{asset('app-assets/vendors/css/forms/selects/select2.min.css')}}">
<style>
    .timeline { list-style:none; padding:0; margin:0; position:relative; }
    .timeline:before { content:""; position:absolute; left:15px; top:4px; bottom:4px; width:2px; background:#e3e3e3; }
    .timeline > li { position:relative; padding:0 0 18px 44px; }
    .timeline > li .dot { position:absolute; left:8px; top:2px; width:16px; height:16px; border-radius:50%; background:#fff; border:3px solid #29b6f6; }
    .timeline > li.done .dot { border-color:#7bb13c; }
    .timeline .t-time { font-size:.78rem; color:#6d6d6d; }
</style>
@endpush

@push('breadcrumb')
<h3 class="content-header-title">Order {{ $order->order_no }}</h3>
<div class="row breadcrumbs-top">
	<div class="breadcrumb-wrapper col-12">
		<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="{{route('orders')}}">Orders</a></li>
			<li class="breadcrumb-item active">{{ $order->order_no }}</li>
		</ol>
	</div>
</div>
@endpush

@push('breadcrumb-button')
<a href="{{ route('orders') }}" class="btn btn-outline-secondary mr-1"><i class="la la-arrow-left"></i> Back</a>
<a href="{{ route('order.slip', $order) }}" target="_blank" class="btn btn-outline-primary mr-1"><i class="la la-print"></i> Slip</a>
<a href="{{ route('order.invoice', $order) }}" class="btn btn-outline-success mr-1"><i class="la la-file-pdf-o"></i> Invoice</a>
@if(!empty($waLink))
<a href="{{ $waLink }}" target="_blank" class="btn btn-outline-success"><i class="la la-whatsapp"></i> WhatsApp</a>
@endif
@endpush

@section('content')
<div class="row">
    <div class="col-md-7 col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Order details
                    <span class="badge badge-{{ $order->status_color }} ml-1">{{ $order->status_label }}</span>
                </h4>
            </div>
            <div class="card-content collapse show">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <tbody>
                                <tr><th style="width:38%">Order number</th><td>{{ $order->order_no }}</td></tr>
                                <tr><th>Customer</th><td>{{ optional($order->customer)->fullname }} <br><small class="text-muted">{{ optional($order->customer)->phone }}</small></td></tr>
                                <tr><th>Assigned to</th><td>{{ $order->assignedTo->fullname ?? '—' }}</td></tr>
                                <tr><th>Description / Measurement</th><td style="white-space:pre-line">{{ $order->description ?: '—' }}</td></tr>
                                <tr><th>Received on</th><td>{{ optional($order->recieved_on)->format('d M Y') }} @if($order->recieved_by) ({{ $order->recieved_by }}) @endif</td></tr>
                                <tr><th>Collect on</th><td>{{ optional($order->collecting_on)->format('d M Y') }}</td></tr>
                                <tr><th>Amount charged</th><td>{{ $order->amount_charged }}</td></tr>
                                <tr><th>Amount paid</th><td>{{ $order->amount_paid }}</td></tr>
                                <tr><th>Balance</th><td><strong>{{ $order->balance }}</strong></td></tr>
                                @if($order->completed_at)
                                    <tr><th>Completed at</th><td>{{ $order->completed_at->format('d M Y, h:i A') }}</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Status &amp; assignment</h4></div>
            <div class="card-content collapse show">
                <div class="card-body">
                    @if(auth()->user()->isAdmin())
                        <form method="post" action="{{ route('order.assign', $order) }}" class="mb-2">
                            @csrf
                            <label>Assign to employee</label>
                            <div class="input-group">
                                <select name="assigned_to" class="select2 form-control">
                                    <option value="">— not assigned —</option>
                                    @foreach($staff as $person)
                                        <option value="{{ $person->id }}" @if($order->assigned_to == $person->id) selected @endif>{{ $person->fullname }}</option>
                                    @endforeach
                                </select>
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="submit">Assign</button>
                                </div>
                            </div>
                        </form>
                        <hr>
                    @endif

                    <form method="post" action="{{ route('order.status', $order) }}">
                        @csrf
                        <label>Update status</label>
                        <div class="form-group">
                            <select name="status" class="form-control">
                                @foreach(\App\Models\Orders::STATUSES as $status)
                                    <option value="{{ $status }}" @if($order->status === $status) selected @endif>{{ ucwords(str_replace('_',' ',$status)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <input type="text" name="notes" class="form-control" placeholder="Note (optional)" maxlength="200">
                        </div>
                        <button class="btn btn-primary" type="submit">Save status</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-5 col-12">
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Status timeline</h4></div>
            <div class="card-content collapse show">
                <div class="card-body">
                    <ul class="timeline">
                        @forelse($order->history as $entry)
                            <li class="{{ $entry->status === $order->status ? 'done' : '' }}">
                                <span class="dot"></span>
                                <div>
                                    <span class="badge badge-{{ ['pending'=>'warning','in_progress'=>'info','completed'=>'success','delivered'=>'primary','cancelled'=>'danger'][$entry->status] ?? 'secondary' }}">
                                        {{ ucwords(str_replace('_',' ',$entry->status)) }}
                                    </span>
                                    @if($entry->from_status && $entry->from_status !== $entry->status)
                                        <small class="text-muted">from {{ str_replace('_',' ',$entry->from_status) }}</small>
                                    @endif
                                    <div class="t-time">
                                        {{ $entry->created_at->format('d M Y, h:i A') }}
                                        @if($entry->user) • by {{ $entry->user->name }} @endif
                                    </div>
                                    @if($entry->notes)<div>{{ $entry->notes }}</div>@endif
                                </div>
                            </li>
                        @empty
                            <li>No status history yet.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('page-js')
<script src="{{asset('app-assets/vendors/js/forms/select/select2.full.min.js')}}"></script>
<script>
$(function () {
    $('.select2').select2();
});
</script>
@endpush

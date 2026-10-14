@extends('layouts.app')

@push('breadcrumb')
<h3 class="content-header-title">My Work</h3>
<div class="row breadcrumbs-top">
	<div class="breadcrumb-wrapper col-12">
		<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="{{route('dashboard')}}">Home</a></li>
			<li class="breadcrumb-item active">My Work</li>
		</ol>
	</div>
</div>
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Orders assigned to me</h4>
            </div>
            <div class="card-content collapse show">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Order No</th>
                                    <th>Customer</th>
                                    <th>Work / Measurement</th>
                                    <th>Status</th>
                                    <th>Collect on</th>
                                    <th>Update</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($orders as $order)
                                    <tr>
                                        <td><a href="{{ route('order.show', $order) }}">{{ $order->order_no }}</a></td>
                                        <td>{{ optional($order->customer)->fullname }}<br><small class="text-muted">{{ optional($order->customer)->phone }}</small></td>
                                        <td style="max-width:280px;white-space:pre-line">{{ \Illuminate\Support\Str::limit($order->description, 140) ?: '—' }}</td>
                                        <td><span class="badge badge-{{ $order->status_color }}">{{ $order->status_label }}</span></td>
                                        <td>{{ optional($order->collecting_on)->format('d M Y') }}</td>
                                        <td>
                                            <form method="post" action="{{ route('order.status', $order) }}" class="d-flex" style="gap:6px;min-width:230px">
                                                @csrf
                                                <select name="status" class="form-control form-control-sm">
                                                    @foreach(\App\Models\Orders::STATUSES as $status)
                                                        <option value="{{ $status }}" @if($order->status === $status) selected @endif>{{ ucwords(str_replace('_',' ',$status)) }}</option>
                                                    @endforeach
                                                </select>
                                                <button class="btn btn-sm btn-primary" type="submit">Save</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-4">No orders are assigned to you yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

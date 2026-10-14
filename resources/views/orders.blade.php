@extends('layouts.app')

@push('page-css')
<link rel="stylesheet" type="text/css" href="{{asset('app-assets/vendors/css/forms/selects/select2.min.css')}}">
<style>
    #prev-measurements-wrap small { display:block; margin-top:4px; color:#6d6d6d; }
    .status-badge { font-size:.78rem; padding:.35em .7em; }
</style>
@endpush

@push('breadcrumb')
<h3 class="content-header-title">Orders</h3>
<div class="row breadcrumbs-top">
	<div class="breadcrumb-wrapper col-12">
		<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="{{route('dashboard')}}">Home</a></li>
			<li class="breadcrumb-item"><a href="#">Orders</a></li>
			<li class="breadcrumb-item active">All Orders</li>
		</ol>
	</div>
</div>
@endpush

@push('breadcrumb-button')
<x-buttons.primary :text="'Add Order'" :target="'#add-order'" />
@endpush

@section('content')

<section id="html5">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
			<h4 class="card-title">Orders List</h4>
            <a class="heading-elements-toggle"><i class="la la-ellipsis-v font-medium-3"></i></a>
            <div class="heading-elements">
              <ul class="list-inline mb-0">
                <li><a data-action="collapse"><i class="ft-minus"></i></a></li>
                <li><a data-action="reload"><i class="ft-rotate-cw"></i></a></li>
                <li><a data-action="expand"><i class="ft-maximize"></i></a></li>
                <li><a data-action="close"><i class="ft-x"></i></a></li>
              </ul>
            </div>
          </div>
          <div class="card-content collapse show">
            <div class="card-body card-dashboard">

                <table class="table table-striped table-bordered dataex-html5-export">
                  <thead>
                    <tr>
						<th>Order No</th>
						<th>Customer</th>
						<th>Description</th>
						<th>Status</th>
						<th>Assigned</th>
						<th>Date Received</th>
						<th>Amount</th>
						<th>Paid</th>
						<th>Balance</th>
						<th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    @if (!empty($orders->count()))
                        @foreach ($orders as $order)
                          <tr>
                            <td><strong>{{ $order->order_no }}</strong></td>
                            <td>{{ optional($order->customer)->fullname }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($order->description, 60) }}</td>
                            <td><span class="badge badge-{{ $order->status_color }} status-badge">{{ $order->status_label }}</span></td>
                            <td>{{ $order->assignedTo->fullname ?? '—' }}</td>
							<td>{{ optional($order->recieved_on)->format('d M Y') }}</td>
							<td>{{ $order->amount_charged }}</td>
							<td>{{ $order->amount_paid }}</td>
							<td>{{ $order->balance }}</td>
                            <td>
                              <a href="#" class="float-md-right" data-toggle="dropdown" aria-expanded="false"><i class="material-icons">more_vert</i></a>
                              <div class="dropdown-menu">
                                  <a href="{{ route('order.show', $order) }}" class="dropdown-item">
                                    <i class="la la-eye"></i>View
                                  </a>
                                  <a href="{{ route('order.slip', $order) }}" target="_blank" class="dropdown-item">
                                    <i class="la la-print"></i>Order Slip
                                  </a>
                                  <a href="{{ route('order.invoice', $order) }}" target="_blank" class="dropdown-item">
                                    <i class="la la-file-pdf-o"></i>Invoice (PDF)
                                  </a>
                                  <div class="dropdown-divider"></div>
                                  <a href="javascript:void(0)" class="dropdown-item assignbtn" data-id="{{ $order->id }}" data-order="{{ $order->order_no }}">
                                    <i class="la la-user-plus"></i>Assign to employee
                                  </a>
                                  <div class="dropdown-divider"></div>
                                  <a data-id="{{ $order->id }}" href="javascript:void(0)" aria-haspopup="true" data-toggle="modal" aria-expanded="true" class="dropdown-item deletebtn">
                                    <i class="la la-trash"></i>Delete
                                  </a>
                              </div>
                            </td>
                          </tr>
                        @endforeach
						<x-modals.delete :route="'order.destroy'" :title="'Customer Order'" />
                    @endif
                  </tbody>

                </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>


<!-- add order modal starts here -->
<div class="modal fade text-left" id="add-order" tabindex="-1" role="dialog" aria-labelledby="AddOrder" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<label class="modal-title text-text-bold-600" id="myModalLabel33">Add Order</label>
				<button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<form method="post" action="{{route('orders')}}">
				@csrf
				<div class="modal-body">
					<label>Select Customer: </label>
					<div class="form-group">
						<select name="customer" id="order-customer" title="select customer" class="select2 form-control">
							<optgroup>
								@if (!empty($customers->count()))
									@foreach ($customers as $customer)
									<option value="{{$customer->id}}" @if(request('customer') == $customer->id) selected @endif>{{$customer->fullname}}</option>
									@endforeach
								@endif
							</optgroup>
						</select>
					</div>

					<div id="prev-measurements-wrap" class="form-group">
						<label>Previous measurement (carry forward): </label>
						<select id="prev-measurements" class="form-control">
							<option value="">Select the customer first…</option>
						</select>
						<small>Pick an earlier measurement and it will be copied into the description box below. Edit it if the body has changed.</small>
					</div>

					<label>Description / Measurement: </label>
					<div class="form-group">
						<textarea class="form-control" placeholder="enter description / measurements here" name="description" id="order-description" rows="5"></textarea>
					</div>

					<label>Date Received: </label>
					<div class="form-group">
						<input type="date" name="recieved_on" class="form-control" value="{{old('recieved_on', date('Y-m-d'))}}">
					</div>

					<label>Received By: </label>
					<div class="form-group">
						<input type="text" placeholder="Enter receiver name" name="receiver" class="form-control" value="{{old('receiver')}}">
					</div>

					<label>Amount: </label>
					<div class="form-group">
						<input type="number" step="0.01" placeholder="Enter amount charged" name="amount_charged" class="form-control" value="{{old('amount_charged')}}">
					</div>

					<label>Paid: </label>
					<div class="form-group">
						<input type="number" step="0.01" name="amount_paid" class="form-control" placeholder="Enter amount paid" value="{{old('amount_paid')}}">
					</div>

					<label>Date To Collect: </label>
					<div class="form-group">
						<input type="date" class="form-control" name="collecting_on" value="{{old('collecting_on')}}">
					</div>

					<label>Assign to employee: </label>
					<div class="form-group">
						<select name="assigned_to" class="select2 form-control">
							<option value="">— not assigned —</option>
							@if ($staff->count())
								@foreach ($staff as $person)
									<option value="{{ $person->id }}" @if(old('assigned_to') == $person->id) selected @endif>{{ $person->fullname }}</option>
								@endforeach
							@endif
						</select>
					</div>
				</div>
				<div class="modal-footer">
					<input type="reset" class="btn btn-outline-secondary btn-lg" data-dismiss="modal" value="close">
					<button class="btn btn-outline-primary btn-lg" type="submit">Submit</button>
				</div>
			</form>
		</div>
	</div>
</div>
<!-- add order modal ends here -->

<!-- assign order modal starts here -->
<div class="modal fade text-left" id="assign-order" tabindex="-1" role="dialog" aria-labelledby="AssignOrder" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<label class="modal-title text-text-bold-600">Assign order <strong id="assign-order-no"></strong></label>
				<button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<form method="post" id="assign-form" action="">
				@csrf
				<div class="modal-body">
					<div class="form-group">
						<label>Choose employee: </label>
						<select name="assigned_to" class="select2 form-control">
							<option value="">— not assigned —</option>
							@if ($staff->count())
								@foreach ($staff as $person)
									<option value="{{ $person->id }}">{{ $person->fullname }}</option>
								@endforeach
							@endif
						</select>
					</div>
				</div>
				<div class="modal-footer">
					<input type="reset" class="btn btn-outline-secondary btn-lg" data-dismiss="modal" value="close">
					<button class="btn btn-outline-primary btn-lg" type="submit">Assign</button>
				</div>
			</form>
		</div>
	</div>
</div>
<!-- assign order modal ends here -->

@endsection


@push('page-js')
<script src="{{asset('app-assets/vendors/js/forms/select/select2.full.min.js')}}"></script>
<script>
$(function () {
    $('.select2').select2();

    var measurementsBase = '{{ route('customers') }}';

    // Load previous measurements when a customer is chosen.
    $('#order-customer').on('change', function () {
        var id = $(this).val();
        var $select = $('#prev-measurements');
        $select.empty().append('<option value="">Select a previous measurement…</option>');
        window.__measurements = {};

        if (!id) { return; }

        $.getJSON(measurementsBase + '/' + id + '/measurements')
            .done(function (rows) {
                if (!rows.length) {
                    $select.append('<option value="">No previous measurements yet for this customer</option>');
                    return;
                }
                window.__measurements = {};
                rows.forEach(function (m) {
                    window.__measurements[m.id] = m;
                    $select.append($('<option/>').val(m.id).text(m.title + ' — ' + m.date));
                });
            })
            .fail(function () {
                $select.append('<option value="">Could not load measurements</option>');
            });
    });

    // Copy the chosen measurement into the description box.
    $('#prev-measurements').on('change', function () {
        var m = (window.__measurements || {})[$(this).val()];
        if (m && m.details) {
            $('#order-description').val(m.details).trigger('input');
        }
    });

    // Quick-assign modal.
    $('.assignbtn').on('click', function () {
        $('#assign-order-no').text($(this).data('order'));
        $('#assign-form').attr('action', 'orders/' + $(this).data('id') + '/assign');
        $('#assign-order').modal('show');
    });

    // Prefill the "add order" modal from an employee/order deep link.
    @if(request('new'))
        $('#add-order').modal('show');
    @endif
});
</script>
@endpush

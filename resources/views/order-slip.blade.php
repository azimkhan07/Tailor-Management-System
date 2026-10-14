<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order Slip {{ $order->order_no }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color:#222; margin:0; padding:24px; background:#f4f4f4; }
        .slip { max-width:640px; margin:0 auto; background:#fff; padding:28px; border:1px solid #ddd; }
        .head { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2px solid #333; padding-bottom:12px; }
        .head h1 { margin:0; font-size:20px; letter-spacing:1px; }
        .head .no { text-align:right; }
        .head .no strong { font-size:18px; }
        .muted { color:#777; font-size:12px; }
        table { width:100%; border-collapse:collapse; margin-top:16px; }
        th, td { text-align:left; padding:7px 6px; font-size:14px; vertical-align:top; }
        .kv th { width:34%; color:#555; font-weight:600; }
        .box td, .box th { border:1px solid #ccc; }
        .meas { white-space:pre-line; }
        .totals td { font-size:15px; }
        .totals .label { text-align:right; }
        .sign { margin-top:40px; display:flex; justify-content:space-between; }
        .sign div { border-top:1px solid #999; width:40%; text-align:center; padding-top:6px; font-size:12px; color:#666; }
        .actions { max-width:640px; margin:0 auto 14px; text-align:right; }
        .actions button { padding:9px 18px; background:#333; color:#fff; border:0; border-radius:4px; cursor:pointer; }
        @media print { body { background:#fff; padding:0; } .actions { display:none; } .slip { border:0; } }
    </style>
</head>
<body onload="window.print()">
    <div class="actions"><button onclick="window.print()">Print</button></div>
    <div class="slip">
        <div class="head">
            <div>
                <h1>{{ strtoupper(setting('app_name', config('app.name'))) }}</h1>
                <div class="muted">Order Slip</div>
            </div>
            <div class="no">
                <div class="muted">Order No</div>
                <strong>{{ $order->order_no }}</strong><br>
                <span class="muted">{{ $order->created_at->format('d M Y, h:i A') }}</span>
            </div>
        </div>

        <table class="kv">
            <tr><th>Customer</th><td>{{ optional($order->customer)->fullname }}</td></tr>
            <tr><th>Phone</th><td>{{ optional($order->customer)->phone }}</td></tr>
            <tr><th>Received on</th><td>{{ optional($order->recieved_on)->format('d M Y') }}</td></tr>
            <tr><th>Collect on</th><td>{{ optional($order->collecting_on)->format('d M Y') }}</td></tr>
            <tr><th>Assigned to</th><td>{{ $order->assignedTo->fullname ?? '—' }}</td></tr>
            <tr><th>Received by</th><td>{{ $order->recieved_by ?: '—' }}</td></tr>
        </table>

        <table class="box">
            <thead><tr><th>Description / Measurement</th></tr></thead>
            <tbody><tr><td class="meas">{!! nl2br(e($order->description ?: '—')) !!}</td></tr></tbody>
        </table>

        <table class="box totals">
            <tr><th class="label">Amount charged</th><td>{{ $order->amount_charged }}</td></tr>
            <tr><th class="label">Amount paid</th><td>{{ $order->amount_paid }}</td></tr>
            <tr><th class="label">Balance</th><td><strong>{{ $order->balance }}</strong></td></tr>
        </table>

        <div class="sign">
            <div>Customer</div>
            <div>{{ strtoupper(setting('app_name', config('app.name'))) }}</div>
        </div>
    </div>
</body>
</html>

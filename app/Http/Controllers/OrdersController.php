<?php

namespace App\Http\Controllers;

use App\Models\Orders;
use App\Models\Staff;
use App\Models\Customer;
use App\Models\Measurement;
use App\Services\OrderNotifier;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class OrdersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $title = "Orders";
        $orders = Orders::with(['customer', 'assignedTo'])->latest()->get();
        $customers = Customer::orderBy('fullname')->get();
        $staff = Staff::orderBy('fullname')->get();

        return view('orders', compact('title', 'customers', 'orders', 'staff'));
    }

    /**
     * Store a newly created order and remember its measurement for next time.
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'customer' => 'required|exists:customers,id',
            'description' => 'nullable|max:2000',
            'recieved_on' => 'required|date',
            'receiver' => 'nullable|string|max:100',
            'amount_charged' => 'required|numeric',
            'amount_paid' => 'required|numeric',
            'collecting_on' => 'required|date',
            'assigned_to' => 'nullable|exists:staff,id',
            'status' => 'nullable|in:' . implode(',', Orders::STATUSES),
        ]);

        $order = Orders::create([
            'customer_id' => $request->customer,
            'description' => $request->description,
            'status' => $request->status ?: 'pending',
            'assigned_to' => $request->assigned_to ?: null,
            'recieved_on' => $request->recieved_on,
            'recieved_by' => $request->receiver,
            'amount_charged' => $request->amount_charged,
            'amount_paid' => $request->amount_paid,
            'collecting_on' => $request->collecting_on,
        ]);

        $this->rememberMeasurement($order);

        return back()->with([
            'message' => "Order {$order->order_no} has been created. Slip is ready to print.",
            'alert-type' => 'success',
        ]);
    }

    /**
     * Show a single order with its status timeline.
     */
    public function show(Orders $order)
    {
        $this->authorizeOrder($order);

        $order->load(['customer', 'assignedTo', 'history.user']);
        $staff = Staff::orderBy('fullname')->get();
        $waLink = app(OrderNotifier::class)->whatsappLink($order);

        return view('order-show', compact('order', 'staff', 'waLink'));
    }

    /**
     * Assign (or unassign) the order to a staff member. Admin only.
     */
    public function assign(Request $request, Orders $order)
    {
        $request->validate([
            'assigned_to' => 'nullable|exists:staff,id',
        ]);

        $order->update(['assigned_to' => $request->assigned_to ?: null]);

        $name = $order->assignedTo->fullname ?? 'nobody';
        $order->history()->create([
            'from_status' => $order->status,
            'status' => $order->status,
            'user_id' => auth()->id(),
            'notes' => "Assigned to {$name}",
        ]);

        return back()->with([
            'message' => "Order {$order->order_no} assigned to {$name}.",
            'alert-type' => 'success',
        ]);
    }

    /**
     * Move the order to a new status, record the timeline entry and notify on completion.
     */
    public function updateStatus(Request $request, Orders $order)
    {
        $this->authorizeOrder($order);

        $request->validate([
            'status' => 'required|in:' . implode(',', Orders::STATUSES),
            'notes' => 'nullable|max:200',
        ]);

        if ($request->status === $order->status) {
            return back()->with(['message' => 'Status is already up to date.', 'alert-type' => 'info']);
        }

        $from = $order->status;
        $order->status = $request->status;
        $order->status_updated_at = now();

        if (in_array($request->status, ['completed', 'delivered'], true)) {
            $order->completed_at = $order->completed_at ?: now();
        }

        $order->save();

        $order->history()->create([
            'from_status' => $from,
            'status' => $request->status,
            'user_id' => auth()->id(),
            'notes' => $request->notes,
        ]);

        $notification = [
            'message' => "Order {$order->order_no} moved to " . $order->status_label . '.',
            'alert-type' => 'success',
        ];

        if ($request->status === 'completed') {
            $result = app(OrderNotifier::class)->sendCompletion($order);
            $notification = [
                'message' => $result['sent']
                    ? "Order {$order->order_no} completed. Customer notified via {$result['driver']}."
                    : "Order {$order->order_no} completed. Notification not sent: {$result['reason']}",
                'alert-type' => $result['sent'] ? 'success' : 'warning',
            ];
        }

        return back()->with($notification);
    }

    /**
     * Printable order slip (created when the order is placed).
     */
    public function slip(Orders $order)
    {
        $this->authorizeOrder($order);

        $order->load('customer');

        return view('order-slip', compact('order'));
    }

    /**
     * PDF invoice (generated when the order is complete).
     */
    public function invoice(Orders $order)
    {
        $this->authorizeOrder($order);

        $order->load('customer');

        $pdf = Pdf::loadView('invoice', compact('order'));

        return $pdf->download('invoice-' . $order->order_no . '.pdf');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $order = Orders::find($request->id);
        $order->delete();

        return back()->with([
            'message' => "Customer order deleted successfully!!",
            'alert-type' => 'success',
        ]);
    }

    /**
     * Save the order description as a reusable measurement for the customer.
     */
    protected function rememberMeasurement(Orders $order): void
    {
        if (blank($order->description)) {
            return;
        }

        Measurement::create([
            'customer_id' => $order->customer_id,
            'title' => 'Order ' . $order->order_no,
            'details' => $order->description,
        ]);
    }

    /**
     * Employees may only touch orders assigned to them; admins can touch any.
     */
    protected function authorizeOrder(Orders $order): void
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return;
        }

        abort_unless(
            $user->staff_id && (int) $order->assigned_to === (int) $user->staff_id,
            403,
            'This order is not assigned to you.'
        );
    }
}

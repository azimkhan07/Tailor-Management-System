<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\Orders;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class EmployeeController extends Controller
{
    /**
     * Orders assigned to the logged-in employee.
     */
    public function work()
    {
        $title = 'My Work';
        $staffId = auth()->user()->staff_id;

        $orders = Orders::with('customer')
            ->where('assigned_to', $staffId)
            ->latest()
            ->get();

        return view('employee.work', compact('title', 'orders'));
    }

    /**
     * Month calendar with shop-closed days and leaves.
     */
    public function calendar(Request $request)
    {
        $title = 'Calendar';
        $isAdmin = auth()->user()->isAdmin();

        try {
            $month = $request->filled('month')
                ? Carbon::createFromFormat('Y-m', $request->input('month'))->startOfMonth()
                : Carbon::now()->startOfMonth();
        } catch (\Throwable $e) {
            $month = Carbon::now()->startOfMonth();
        }

        $monthEnd = $month->copy()->endOfMonth();

        $holidays = Holiday::orderByRaw('date is null, date')->get();

        $leavesQuery = Leave::with('user')
            ->whereDate('start_date', '<=', $monthEnd)
            ->whereDate('end_date', '>=', $month);

        $leaves = $isAdmin
            ? $leavesQuery->get()
            : (clone $leavesQuery)->where('user_id', auth()->id())->get();

        $myLeaves = auth()->user()->leaves;
        $pendingLeaves = $isAdmin ? Leave::with('user')->where('status', 'pending')->latest()->get() : collect();

        // Build the grid data for the view.
        $days = [];
        $cursor = $month->copy();
        while ($cursor->lte($monthEnd)) {
            $dayLeaves = $leaves->filter(
                fn ($leave) => $leave->start_date->lte($cursor) && $leave->end_date->gte($cursor)
            );

            $holiday = $holidays->first(fn ($h) => $h->matches($cursor->toDateString()));

            $days[] = [
                'date' => $cursor->toDateString(),
                'day' => (int) $cursor->format('j'),
                'isToday' => $cursor->isToday(),
                'holiday' => $holiday?->title,
                'leaves' => $dayLeaves->map(fn ($l) => [
                    'name' => $l->user->name ?? 'User',
                    'status' => $l->status,
                ])->values(),
            ];

            $cursor->addDay();
        }

        $leadingBlanks = (int) $month->copy()->startOfMonth()->dayOfWeek; // 0 = Sunday
        $prev = $month->copy()->subMonth()->format('Y-m');
        $next = $month->copy()->addMonth()->format('Y-m');

        return view('employee.calendar', compact(
            'title', 'isAdmin', 'month', 'days', 'leadingBlanks',
            'holidays', 'myLeaves', 'pendingLeaves', 'prev', 'next'
        ));
    }

    /**
     * Employee applies for leave.
     */
    public function applyLeave(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|max:200',
        ]);

        Leave::create([
            'user_id' => auth()->id(),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return back()->with([
            'message' => 'Leave request submitted. You will be informed once it is reviewed.',
            'alert-type' => 'success',
        ]);
    }

    /**
     * Admin approves or rejects a leave request.
     */
    public function decideLeave(Request $request, Leave $leave)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'notes' => 'nullable|max:200',
        ]);

        $leave->update([
            'status' => $request->status,
            'decided_by' => auth()->id(),
            'decided_at' => now(),
            'notes' => $request->notes,
        ]);

        return back()->with([
            'message' => "Leave {$request->status} for {$leave->user->name}.",
            'alert-type' => 'success',
        ]);
    }
}

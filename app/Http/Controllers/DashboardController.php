<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Orders;
use App\Models\Customer;
use App\Models\Expense;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(){
        if (auth()->user()->isEmployee()) {
            return redirect()->route('my-work');
        }

        $title = "Dashboard";

        $total_income = DB::table('incomes')->whereNull('deleted_at')->whereDate('created_at', '>', Carbon::now()->subDays(30))->sum("amount");
        $total_expense = DB::table('expenses')->whereDate('created_at', '>', Carbon::now()->subDays(30))->sum('price');

        $chartData = [
            'labels' => ['Total Customers', 'Customers Orders', 'Total Expenses'],
            'datasets' => [
                [
                    'data' => [Customer::count(), Orders::count(), Expense::count()],
                    'backgroundColor' => ['#36A2EB', '#7bb13c', '#FF6384'],
                    'hoverBackgroundColor' => ['#36A2EB', '#7bb13c', '#FF6384'],
                ],
            ],
        ];

        return view('dashboard', compact(
            'title','chartData',
            'total_income','total_expense',
        ));
    }
}

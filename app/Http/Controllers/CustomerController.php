<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $title ="Customers";
        $customers =Customer::get();
        return view('customers',compact('title','customers'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request,[
            'fullname'=>'required|max:100',
            'address'=>'required|max:200',
            'phone'=>'required|max:15',
            'city'=>'required|max:200',
            'email'=>'required|email',
            'comment'=>'max:200',
            'gender'=>'required'
        ]);
        Customer::create($request->only([
            'fullname',
            'address',
            'phone',
            'city',
            'email',
            'comment',
            'gender',
        ]));
        $notification = array(
            'message'=>"Customer has beed added successfully!!",
            'alert-type'=>'success'
        );
        return back()->with($notification);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Return the saved measurements of a customer (used to carry an old
     * measurement forward when the same customer comes back).
     */
    public function measurements(Customer $customer)
    {
        return response()->json(
            $customer->measurements()
                ->take(20)
                ->get()
                ->map(fn ($measurement) => [
                    'id' => $measurement->id,
                    'title' => $measurement->title,
                    'details' => $measurement->details,
                    'date' => optional($measurement->created_at)->format('d M Y, h:i A'),
                ])
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $customer = Customer::findOrFail($request->id);
        $customer->delete();
        $notification = array(
            'message'=>"Customer deleted successfully!!!",
            'alert-type'=>'success',
        );
        return back()->with($notification);
    }
}

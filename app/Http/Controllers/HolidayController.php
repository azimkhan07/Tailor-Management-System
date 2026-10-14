<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|max:100',
            'date' => 'nullable|required_without:weekday|date',
            'weekday' => 'nullable|required_without:date|integer|between:0,6',
        ]);

        Holiday::create([
            'title' => $request->title,
            'date' => $request->date ?: null,
            'weekday' => $request->date ? null : $request->weekday,
        ]);

        return back()->with([
            'message' => 'Shop closed day added.',
            'alert-type' => 'success',
        ]);
    }

    public function destroy(Holiday $holiday)
    {
        $holiday->delete();

        return back()->with([
            'message' => 'Shop closed day removed.',
            'alert-type' => 'success',
        ]);
    }
}

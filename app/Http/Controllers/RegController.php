<?php

namespace App\Http\Controllers;

use App\Models\Reg;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RegController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Return latest 50 regs for optional admin view
        $regs = Reg::latest()->take(50)->get();
        return Inertia::render('Regs/Index', [
            'regs' => $regs,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'number' => ['required', 'string', 'max:255'],
        ]);

        // Create reg entry (numbers are NOT required to be unique)
        $reg = Reg::create($data);

        return response()->json([
            'success' => true,
            'message' => "Number {$reg->number} was registered.",
            'reg' => $reg,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Reg $reg)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Reg $reg)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Reg $reg)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Reg $reg)
    {
        //
    }
}

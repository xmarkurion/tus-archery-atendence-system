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
        // Paginate regs server-side, 10 per page
        $regs = Reg::orderBy('created_at', 'desc')->paginate(10);
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
        $data = $request->validate([
            'name' => ['nullable','string','max:255'],
            'number' => ['required','string','max:255'],
        ]);

        $reg->fill($data);
        $reg->save();

        if ($request->header('X-Inertia')) {
            return Inertia::location(route('regs.index'));
        }

        return redirect()->route('regs.index')->with('status', 'Registrant updated');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Reg $reg)
    {
        // detach from meetings first
        $reg->meeting()->detach();
        $reg->delete();

        // If AJAX / JSON request, return JSON so frontend can handle without HTML redirect
        if ($request->ajax() || str_contains($request->header('Accept', ''), 'application/json')) {
            return response()->json(['success' => true, 'message' => 'Registrant removed']);
        }

        if ($request->header('X-Inertia')) {
            return Inertia::location(route('regs.index'));
        }

        return redirect()->route('regs.index')->with('status', 'Registrant removed');
    }
}

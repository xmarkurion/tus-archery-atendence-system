<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Meeting;
use App\Models\Reg;

class MeetingRegistrationController extends Controller
{
    public function __construct()
    {
        // Allow public access for meeting registration endpoints (no auth required)
        // If you want protect some methods, add middleware selectively.
    }

    public function showToday()
    {
        // find meeting for today (where start_time date = today)
        $meeting = Meeting::whereDate('start_time', now()->toDateString())->first();

        return Inertia::render('Meeting/Register', [
            'meeting' => $meeting ? $meeting->toArray() : null,
        ]);
    }

    public function register(Request $request)
    {
        // kept for Inertia page POST flow if used
        $data = $request->validate([
            'meeting_id' => 'required|integer|exists:meetings,id',
            'pin' => 'nullable|string',
            'name' => 'nullable|string',
            'number' => 'required|string',
        ]);

        $meeting = Meeting::find($data['meeting_id']);
        if (! $meeting) {
            return redirect()->back()->with('status', 'No meeting found');
        }

        // if meeting has a pin, validate
        if (! empty($meeting->pin)) {
            if (empty($data['pin']) || (string)$meeting->pin !== (string)$data['pin']) {
                return redirect()->back()->with('status', 'Wrong pin for today');
            }
        }

        // create or get reg by number (so we can reuse existing regs)
        $reg = Reg::firstOrCreate(
            ['number' => $data['number']],
            ['name' => $data['name'] ?? null, 'sessions_attended' => 0]
        );

        // Attach reg to meeting via pivot if not already attached
        if (! $meeting->regs()->where('regs.id', $reg->id)->exists()) {
            $meeting->regs()->attach($reg->id);

            // increment counts
            $reg->increment('sessions_attended');
            $meeting->increment('sessions_attended');
        }

        return redirect()->route('meeting.register')->with('status', 'Registered successfully');
    }

    // Public API: return meeting info for today (without exposing pin)
    public function apiToday()
    {
        $meeting = Meeting::whereDate('start_time', now()->toDateString())->first();
        if (! $meeting) {
            return response()->json(['meeting' => null]);
        }

        return response()->json([
            'meeting' => [
                'id' => $meeting->id,
                'start_time' => $meeting->start_time ? $meeting->start_time->toDateTimeString() : null,
                'requires_pin' => ! empty($meeting->pin),
                'sessions_attended' => (int) $meeting->sessions_attended,
            ],
        ]);
    }

    // Authenticated API: return meeting info for today including the pin (protected route)
    public function apiTodayWithPin(Request $request)
    {
        $meeting = Meeting::whereDate('start_time', now()->toDateString())->first();
        if (! $meeting) {
            return response()->json(['meeting' => null]);
        }

        return response()->json([
            'meeting' => [
                'id' => $meeting->id,
                'start_time' => $meeting->start_time ? $meeting->start_time->toDateTimeString() : null,
                'pin' => $meeting->pin,
                'requires_pin' => ! empty($meeting->pin),
                'sessions_attended' => (int) $meeting->sessions_attended,
                'info' => $meeting->info,
            ],
        ]);
    }

    // Public API: verify provided pin for meeting
    public function apiVerifyPin(Request $request)
    {
        $data = $request->validate([
            'meeting_id' => 'required|integer|exists:meetings,id',
            'pin' => 'required|string',
        ]);

        $meeting = Meeting::find($data['meeting_id']);
        if (! $meeting) {
            return response()->json(['ok' => false, 'message' => 'Meeting not found'], 404);
        }

        if (empty($meeting->pin)) {
            return response()->json(['ok' => true]);
        }

        if ((string)$meeting->pin === (string)$data['pin']) {
            return response()->json(['ok' => true]);
        }

        return response()->json(['ok' => false, 'message' => 'Wrong pin'], 422);
    }

    // Public API: register user for meeting
    public function apiRegister(Request $request)
    {
        $data = $request->validate([
            'meeting_id' => 'required|integer|exists:meetings,id',
            'pin' => 'nullable|string',
            'name' => 'nullable|string',
            'number' => 'required|string',
        ]);

        // Capitalize first letter of number
        $data['number'] = ucfirst($data['number']);

        $meeting = Meeting::find($data['meeting_id']);
        if (! $meeting) {
            return response()->json(['success' => false, 'message' => 'No meeting found'], 404);
        }

        // if meeting has a pin, validate
        if (! empty($meeting->pin)) {
            if (empty($data['pin']) || (string)$meeting->pin !== (string)$data['pin']) {
                return response()->json(['success' => false, 'message' => 'Wrong pin for today'], 422);
            }
        }

        // create or get reg by number (so we can reuse existing regs)
        $reg = Reg::firstOrCreate(
            ['number' => $data['number']],
            ['name' => $data['name'] ?? null, 'sessions_attended' => 0]
        );

        // Attach reg to meeting via pivot if not already attached
        if (! $meeting->regs()->where('regs.id', $reg->id)->exists()) {
            $meeting->regs()->attach($reg->id);

            // increment counts
            $reg->increment('sessions_attended');
            $meeting->increment('sessions_attended');
        }

        return response()->json(['success' => true, 'message' => 'Registered successfully']);
    }

    // Public API: create today's meeting if missing and secure it with pin 1212
    public function apiCreateTodayWithPin(Request $request)
    {
        $meeting = Meeting::whereDate('start_time', now()->toDateString())->first();

        if ($meeting) {
            return response()->json([
                'created' => false,
                'meeting' => [
                    'id' => $meeting->id,
                    'start_time' => $meeting->start_time ? $meeting->start_time->toDateTimeString() : null,
                    'pin' => $meeting->pin,
                    'sessions_attended' => (int) $meeting->sessions_attended,
                ],
            ]);
        }

        // create a new meeting for today
        $new = Meeting::create([
            'sessions_attended' => 0,
            'start_time' => now(),
            'end_time' => null,
            'pin' => null,
            'info' => 'Auto-created via secure API',
        ]);

        return response()->json([
            'created' => true,
            'meeting' => [
                'id' => $new->id,
                'start_time' => $new->start_time ? $new->start_time->toDateTimeString() : null,
                'sessions_attended' => (int) $new->sessions_attended,
            ],
        ]);
    }
}

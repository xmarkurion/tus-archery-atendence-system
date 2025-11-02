<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MeetingSetting;
use App\Models\Meeting;
use Inertia\Inertia;

class MeetingSettingsController extends Controller
{
    public function index()
    {
        $settings = MeetingSetting::first();
        // paginate meetings server-side, 10 per page
        $meetings = Meeting::orderBy('start_time', 'desc')->paginate(10);

        return Inertia::render('MeetingsSettings', [
            'settings' => $settings ? $settings->toArray() : null,
            // pass the paginator directly so Inertia serializes it into { data, meta, links }
            'meetings' => $meetings,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'selected_days' => 'array',
            'selected_days.*' => 'string',
            // accept string so we can handle both H:i and H:i:s (browser time input gives H:i)
            'default_start_time' => 'nullable|string',
            'default_duration' => 'nullable|integer|min:1|max:1440',
        ]);

        $settings = MeetingSetting::first();
        if (! $settings) {
            $settings = new MeetingSetting();
            $settings->enabled = true;
        }

        $settings->selected_days = $data['selected_days'] ?? [];
        if (array_key_exists('default_start_time', $data)) {
            $dst = $data['default_start_time'];
            if ($dst) {
                // normalize H:i to H:i:s if needed
                if (preg_match('/^\d{2}:\d{2}$/', $dst)) {
                    $dst = $dst . ':00';
                }
                $settings->default_start_time = $dst;
            } else {
                $settings->default_start_time = null;
            }
        }
        if (array_key_exists('default_duration', $data)) {
            $settings->default_duration = (int) $data['default_duration'];
        }
        $settings->save();

        // Redirect to the Inertia page so the client receives fresh props
        return redirect()->route('meeting.settings.index')->with('status', 'Settings updated');
    }

    public function toggle(Request $request)
    {
        $settings = MeetingSetting::first();
        if (! $settings) {
            $settings = new MeetingSetting();
            $settings->selected_days = ['Friday'];
            $settings->enabled = true;
        }

        $settings->enabled = ! $settings->enabled;
        $settings->save();

        return redirect()->route('meeting.settings.index')->with('status', $settings->enabled ? 'Enabled' : 'Disabled');
    }

    public function runNow(Request $request)
    {
        $settings = MeetingSetting::first();
        if (! $settings || ! $settings->enabled) {
            return redirect()->route('meeting.settings.index')->with('status', 'Automatic creation is disabled');
        }

        $today = now()->format('l'); // full day name
        if (in_array($today, $settings->selected_days ?? [])) {
            // create meeting for today (if not exists)
            $exists = Meeting::whereDate('start_time', now()->toDateString())->exists();
            if (! $exists) {
                // Determine start_time and end_time from settings defaults
                $defaultStart = $settings->default_start_time ?? null; // stored as H:i:s or null
                $defaultDuration = (int) ($settings->default_duration ?? 60);

                // Compose start datetime for today using the default time if valid, otherwise use now()
                $start = now();
                if ($defaultStart) {
                    try {
                        $parts = explode(':', $defaultStart);
                        $hour = isset($parts[0]) ? (int)$parts[0] : now()->hour;
                        $minute = isset($parts[1]) ? (int)$parts[1] : now()->minute;
                        $second = isset($parts[2]) ? (int)$parts[2] : 0;
                        $start = now()->setTime($hour, $minute, $second);
                    } catch (\Throwable $e) {
                        $start = now();
                    }
                }

                $end = (clone $start)->addMinutes($defaultDuration);

                Meeting::create([
                    'sessions_attended' => 0,
                    'start_time' => $start,
                    'end_time' => $end,
                    'info' => 'Auto-created meeting',
                ]);
                return redirect()->route('meeting.settings.index')->with('status', 'Meeting created for today');
            }
            return redirect()->route('meeting.settings.index')->with('status', 'Meeting for today already exists');
        }

        return redirect()->route('meeting.settings.index')->with('status', 'Today is not a selected day');
    }

    // Return meeting details and attendees as JSON (used by the front-end modal)
    public function meetingDetails(Request $request, $id)
    {
        $meeting = Meeting::with(['regs'])->find($id);
        if (! $meeting) {
            return response()->json(['meeting' => null], 404);
        }

        // Format regs/simple data
        $regs = $meeting->regs->map(function ($r) {
            return [
                'id' => $r->id,
                'name' => $r->name,
                'number' => $r->number,
                'sessions_attended' => $r->sessions_attended,
                'created_at' => $r->created_at ? $r->created_at->toDateTimeString() : null,
            ];
        });

        return response()->json([
            'meeting' => [
                'id' => $meeting->id,
                'start_time' => $meeting->start_time ? $meeting->start_time->toDateTimeString() : null,
                'end_time' => $meeting->end_time ? $meeting->end_time->toDateTimeString() : null,
                'info' => $meeting->info,
                'pin' => $meeting->pin,
                'sessions_attended' => (int) $meeting->sessions_attended,
                'regs' => $regs,
            ],
        ]);
    }

    // Update meeting fields: start_time, end_time, info, pin
    public function updateMeeting(Request $request, $id)
    {
        $data = $request->validate([
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date',
            'info' => 'nullable|string',
            'pin' => 'nullable|numeric',
        ]);

        $meeting = Meeting::find($id);
        if (! $meeting) {
            // For Inertia requests we should return a redirect so the client receives a proper Inertia response
            if ($request->header('X-Inertia')) {
                return Inertia::location(route('meeting.settings.index'));
            }
            return redirect()->route('meeting.settings.index')->with('status', 'Meeting not found');
        }

        if (array_key_exists('start_time', $data)) $meeting->start_time = $data['start_time'];
        if (array_key_exists('end_time', $data)) $meeting->end_time = $data['end_time'];
        if (array_key_exists('info', $data)) $meeting->info = $data['info'];
        if (array_key_exists('pin', $data)) $meeting->pin = $data['pin'];

        $meeting->save();

        if ($request->header('X-Inertia')) {
            return Inertia::location(route('meeting.settings.index'));
        }

        return redirect()->route('meeting.settings.index')->with('status', 'Meeting updated');
    }

    // Remove attendee (detach pivot) and decrement counters
    public function removeAttendee(Request $request, $meetingId, $regId)
    {
        // If this request is coming from an XHR/fetch client and the user is not authenticated,
        // return a JSON 401 rather than letting middleware redirect to the login HTML page.
        if (! auth()->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }
        $meeting = Meeting::find($meetingId);
        if (! $meeting) return response()->json(['success' => false, 'message' => 'Meeting not found'], 404);

        $reg = \App\Models\Reg::find($regId);
        if (! $reg) return response()->json(['success' => false, 'message' => 'Registrant not found'], 404);

        // check attachment
        if (! $meeting->regs()->where('regs.id', $reg->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Registrant not attached to meeting'], 400);
        }

        $meeting->regs()->detach($reg->id);

        // decrement counts safely (no negative)
        if ($reg->sessions_attended > 0) $reg->decrement('sessions_attended');
        if ($meeting->sessions_attended > 0) $meeting->decrement('sessions_attended');

        return response()->json(['success' => true, 'message' => 'Registrant removed']);
    }

    // Export meeting to PDF or return printable HTML
    public function exportPdf(Request $request, $id)
    {
        $meeting = Meeting::with('regs')->find($id);
        if (! $meeting) {
            return redirect()->route('meeting.settings.index')->with('status', 'Meeting not found');
        }

        $data = [
            'meeting' => $meeting,
            'day' => $meeting->start_time ? $meeting->start_time->format('l, Y-m-d H:i') : null,
            'attendees_count' => (int) $meeting->sessions_attended,
            'attendees' => $meeting->regs->map(function ($r) {
                return ['name' => $r->name, 'number' => $r->number];
            })->toArray(),
        ];

        // try to generate PDF if Dompdf is installed
        if (class_exists('\Dompdf\\Dompdf')) {
            $html = view('meetings.export', $data)->render();
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            return response($dompdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="meeting-'.$meeting->id.'.pdf"'
            ]);
        }

        // fallback: return printable view
        return view('meetings.export', $data);
    }

    // Render a standalone Inertia page that shows meeting and attendees (used instead of modal)
    public function showMeeting(Request $request, $id)
    {
        $meeting = Meeting::with('regs')->find($id);
        if (! $meeting) {
            return redirect()->route('meeting.settings.index')->with('status', 'Meeting not found');
        }

        return Inertia::render('Meeting/Show', [
            'meeting' => $meeting->toArray(),
        ]);
    }

    // Destroy a meeting and detach associated regs (adjust counters)
    public function destroyMeeting(Request $request, $id)
    {
        $meeting = Meeting::with('regs')->find($id);
        if (! $meeting) {
            if ($request->ajax() || str_contains($request->header('Accept', ''), 'application/json')) {
                return response()->json(['success' => false, 'message' => 'Meeting not found'], 404);
            }
            return redirect()->route('meeting.settings.index')->with('status', 'Meeting not found');
        }

        // Detach regs and decrement their sessions_attended
        foreach ($meeting->regs as $reg) {
            // detach pivot
            $meeting->regs()->detach($reg->id);
            if ($reg->sessions_attended > 0) {
                $reg->decrement('sessions_attended');
            }
        }

        $meeting->delete();

        if ($request->ajax() || str_contains($request->header('Accept', ''), 'application/json')) {
            return response()->json(['success' => true, 'message' => 'Meeting deleted']);
        }

        return redirect()->route('meeting.settings.index')->with('status', 'Meeting deleted');
    }
}

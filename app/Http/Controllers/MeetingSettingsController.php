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
        $meetings = Meeting::orderBy('start_time', 'desc')->get();

        return Inertia::render('MeetingsSettings', [
            'settings' => $settings ? $settings->toArray() : null,
            'meetings' => $meetings->toArray(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'selected_days' => 'array',
            'selected_days.*' => 'string',
        ]);

        $settings = MeetingSetting::first();
        if (! $settings) {
            $settings = new MeetingSetting();
            $settings->enabled = true;
        }

        $settings->selected_days = $data['selected_days'] ?? [];
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
                Meeting::create([
                    'sessions_attended' => 0,
                    'start_time' => now(),
                    'end_time' => null,
                    'info' => 'Auto-created meeting',
                ]);
                return redirect()->route('meeting.settings.index')->with('status', 'Meeting created for today');
            }
            return redirect()->route('meeting.settings.index')->with('status', 'Meeting for today already exists');
        }

        return redirect()->route('meeting.settings.index')->with('status', 'Today is not a selected day');
    }
}

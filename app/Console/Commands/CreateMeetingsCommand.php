<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MeetingSetting;
use App\Models\Meeting;
use Carbon\Carbon;

class CreateMeetingsCommand extends Command
{
    protected $signature = 'meetings:create-daily';

    protected $description = 'Create meetings on selected days based on meeting settings';

    public function handle()
    {
        $settings = MeetingSetting::first();
        if (! $settings) {
            $this->info('No meeting settings found');
            return 0;
        }

        if (! $settings->enabled) {
            $this->info('Automatic meeting creation is disabled');
            return 0;
        }

        $today = now()->format('l');
        if (! in_array($today, $settings->selected_days ?? [])) {
            $this->info("Today ($today) is not a selected day");
            return 0;
        }

        // Create meeting if not exists for today
        $exists = Meeting::whereDate('start_time', now()->toDateString())->exists();
        if ($exists) {
            $this->info('Meeting for today already exists');
            return 0;
        }

        // Determine start_time and end_time using defaults from settings
        $defaultStart = $settings->default_start_time ?? '18:00:00';
        $defaultDuration = (int) ($settings->default_duration ?? 60);

        // Compose start datetime for today using the default time
        // Normalize and parse times like "18:00" or "18:00:00" safely.
        $hour = 18; $minute = 0; $second = 0;
        if (is_string($defaultStart) && preg_match('/^\s*\d{1,2}:\d{2}(:\d{2})?\s*$/', $defaultStart)) {
            $parts = explode(':', trim($defaultStart));
            $hour = (int) ($parts[0] ?? 18);
            $minute = (int) ($parts[1] ?? 0);
            $second = (int) ($parts[2] ?? 0);
            // clamp values to valid ranges
            $hour = max(0, min(23, $hour));
            $minute = max(0, min(59, $minute));
            $second = max(0, min(59, $second));
        } else {
            // try Carbon parse fallback (if someone stored '6pm' or similar)
            try {
                $c = Carbon::parse($defaultStart);
                $hour = $c->hour; $minute = $c->minute; $second = $c->second;
            } catch (\Throwable $ex) {
                // keep defaults
                $hour = 18; $minute = 0; $second = 0;
            }
        }

        $start = Carbon::today()->setTime($hour, $minute, $second);

        $end = (clone $start)->addMinutes($defaultDuration);

        Meeting::create([
            'sessions_attended' => 0,
            'start_time' => $start,
            'end_time' => $end,
            'info' => 'Auto-created by scheduler',
        ]);

        $this->info('Meeting created for today');
        return 0;
    }
}

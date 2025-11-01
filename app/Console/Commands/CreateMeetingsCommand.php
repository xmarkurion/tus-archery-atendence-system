<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MeetingSetting;
use App\Models\Meeting;

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
        $start = now()->startOfDay()->addHours(0);
        try {
            $parts = explode(':', $defaultStart);
            $hour = isset($parts[0]) ? (int)$parts[0] : 18;
            $minute = isset($parts[1]) ? (int)$parts[1] : 0;
            $second = isset($parts[2]) ? (int)$parts[2] : 0;
            $start = now()->setTime($hour, $minute, $second);
        } catch (\Throwable $e) {
            $start = now()->setTime(18,0,0);
        }

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

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

        Meeting::create([
            'sessions_attended' => 0,
            'start_time' => now(),
            'end_time' => null,
            'info' => 'Auto-created by scheduler',
        ]);

        $this->info('Meeting created for today');
        return 0;
    }
}


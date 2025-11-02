<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Meeting;
use App\Models\Reg;

class MeetingSeeder extends Seeder
{
    public function run(): void
    {
        // create 10 meetings
        $meetings = Meeting::factory()->count(100)->create();

        // attach some regs to meetings
        $allRegs = Reg::all();
        foreach ($meetings as $meeting) {
            // attach 5-15 random regs
            $regs = $allRegs->random(min( max(1, $allRegs->count()), 5 + rand(0,10)));
            $meeting->regs()->syncWithoutDetaching($regs->pluck('id')->toArray());
        }
    }
}


<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckSemesterDates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'academic:check-semesters';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and automatically update the active semester based on start and end dates';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = now()->toDateString();
        
        // 1. Check if the currently active semester has ended
        $activeSemester = \Illuminate\Support\Facades\DB::table('semesters')->where('is_active', true)->first();
        if ($activeSemester && $activeSemester->end_date < $today) {
            \Illuminate\Support\Facades\DB::table('semesters')
                ->where('semester_id', $activeSemester->semester_id)
                ->update(['is_active' => false, 'updated_at' => now()]);
                
            $this->info("Deactivated semester: {$activeSemester->name}");
        }

        // 2. Find if there's a semester that should be active today
        $newActiveSemester = \Illuminate\Support\Facades\DB::table('semesters')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->where('is_active', false)
            ->first();

        if ($newActiveSemester) {
            // Ensure any other active semester is deactivated (safeguard)
            \Illuminate\Support\Facades\DB::table('semesters')->update(['is_active' => false]);
            
            \Illuminate\Support\Facades\DB::table('semesters')
                ->where('semester_id', $newActiveSemester->semester_id)
                ->update(['is_active' => true, 'updated_at' => now()]);
                
            $this->info("Activated new semester: {$newActiveSemester->name}");
        } else {
            $this->info("No new semester to activate today.");
        }
    }
}

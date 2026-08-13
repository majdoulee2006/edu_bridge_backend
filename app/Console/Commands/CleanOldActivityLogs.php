<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserActivity;

class CleanOldActivityLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:clean {--days=90 : Number of days to keep logs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean activity logs older than specified days (default 90 days)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $cutoffDate = now()->subDays($days);

        $deletedCount = UserActivity::where('created_at', '<', $cutoffDate)->delete();

        $this->info("Successfully deleted {$deletedCount} activity logs older than {$days} days.");

        return Command::SUCCESS;
    }
}

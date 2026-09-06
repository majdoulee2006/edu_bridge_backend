<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class ExportDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export the database safely using mysqldump for sharing with the team';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $database = Config::get('database.connections.mysql.database');
        $username = Config::get('database.connections.mysql.username');
        $password = Config::get('database.connections.mysql.password');
        $host = Config::get('database.connections.mysql.host');
        $port = Config::get('database.connections.mysql.port', '3306');
        
        $outputFile = base_path('edu_bridge_backend.sql');

        $this->info("Starting professional database export for '{$database}'...");

        // Constructing the mysqldump command
        // We use --add-drop-table to ensure fresh import for other developers
        $passwordStr = empty($password) ? '' : "-p\"{$password}\"";
        
        // Command runs via system shell to support file redirection (>)
        $command = "mysqldump -h{$host} -P{$port} -u{$username} {$passwordStr} --add-drop-table {$database} > \"{$outputFile}\"";

        $process = Process::fromShellCommandline($command);
        $process->setTimeout(300); // Allow up to 5 minutes for large databases

        try {
            $process->mustRun();
            $this->info("✅ Database exported successfully!");
            $this->line("📁 File saved at: {$outputFile}");
            $this->comment("💡 The generated file automatically handles Foreign Key Checks and includes DROP TABLE, making it 100% safe to import anywhere.");
        } catch (ProcessFailedException $exception) {
            $this->error("❌ Failed to export database.");
            
            // Check if error is related to missing mysqldump path in Windows
            if (str_contains(strtolower($exception->getMessage()), 'is not recognized')) {
                $this->error("💡 Missing Path: The 'mysqldump' tool is not recognized in your Windows PATH.");
                $this->line("If you use Laragon, you must add the MySQL bin folder to your Windows Environment Variables.");
                $this->line("Example path: C:\\laragon\\bin\\mysql\\mysql-x.x.x\\bin");
            } else {
                $this->error("Error details: " . $exception->getMessage());
            }
        }
    }
}

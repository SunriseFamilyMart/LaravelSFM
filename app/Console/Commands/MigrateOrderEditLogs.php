<?php

namespace App\Console\Commands;

use App\Services\OrderEditLogService;
use Illuminate\Console\Command;

class MigrateOrderEditLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:migrate-edit-logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing order edit logs to populate action and return_type fields';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting migration of order edit logs...');
        
        $updatedCount = OrderEditLogService::migrateExistingLogs();
        
        $this->info("Migration completed! Updated {$updatedCount} records.");
        
        return Command::SUCCESS;
    }
}

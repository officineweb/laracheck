<?php

namespace App\Console\Commands;

use App\Models\Exception;
use App\Models\Outage;
use Illuminate\Console\Command;

class CleanOldData extends Command
{
    protected $signature = 'data:clean-old
                            {--days= : Number of days to retain data (overrides config)}
                            {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Clean old exceptions and outages based on retention policy';

    public function handle(): int
    {
        $retentionDays = $this->option('days')
            ?? config('laracheck.data_retention_days', 365);

        $isDryRun = $this->option('dry-run');

        $this->info('🧹 Data Cleanup '.($isDryRun ? '(DRY RUN)' : ''));
        $this->newLine();
        $this->info("Retention Period: {$retentionDays} days");
        $this->info('Cutoff Date: '.now()->subDays($retentionDays)->toDateTimeString());
        $this->newLine();

        $cutoffDate = now()->subDays($retentionDays);

        // Count old exceptions
        $oldExceptionsQuery = Exception::where('created_at', '<', $cutoffDate);
        $oldExceptionsCount = $oldExceptionsQuery->count();

        // Count old outages
        $oldOutagesQuery = Outage::where('occurred_at', '<', $cutoffDate);
        $oldOutagesCount = $oldOutagesQuery->count();

        if ($oldExceptionsCount === 0 && $oldOutagesCount === 0) {
            $this->info('✅ No old data to clean up.');

            return self::SUCCESS;
        }

        // Display what will be deleted
        $this->table(
            ['Type', 'Count', 'Action'],
            [
                ['Exceptions', number_format($oldExceptionsCount), $isDryRun ? 'Would delete' : 'Will delete'],
                ['Outages', number_format($oldOutagesCount), $isDryRun ? 'Would delete' : 'Will delete'],
            ]
        );

        if ($isDryRun) {
            $this->newLine();
            $this->info('🔍 Dry run completed. No data was deleted.');
            $this->info('💡 Run without --dry-run to actually delete the data.');

            return self::SUCCESS;
        }

        // Confirm deletion (only in interactive mode)
        if ($this->input->isInteractive()) {
            if (! $this->confirm('Do you want to proceed with the deletion?', true)) {
                $this->info('❌ Cleanup cancelled.');

                return self::SUCCESS;
            }
        }

        // Delete old exceptions
        if ($oldExceptionsCount > 0) {
            $deletedExceptions = $oldExceptionsQuery->delete();
            $this->info("🗑️  Deleted {$deletedExceptions} old exceptions");
        }

        // Delete old outages
        if ($oldOutagesCount > 0) {
            $deletedOutages = $oldOutagesQuery->delete();
            $this->info("🗑️  Deleted {$deletedOutages} old outages");
        }

        $this->newLine();
        $this->info('✅ Data cleanup completed successfully!');

        return self::SUCCESS;
    }
}

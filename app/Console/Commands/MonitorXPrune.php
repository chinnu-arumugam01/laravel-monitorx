<?php

namespace App\Console\Commands;

use App\Models\MonitoringRecord;
use Illuminate\Console\Command;

class MonitorXPrune extends Command
{
    protected $signature = 'monitorx:prune {--days= : Number of days to retain (defaults to config)}';

    protected $description = 'Prune old MonitorX monitoring records';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('monitorx.retention_days', 30));

        if ($days < 1) {
            $this->error('Retention days must be at least 1.');

            return self::FAILURE;
        }

        $deleted = MonitoringRecord::pruneOlderThan($days);

        $this->info("Pruned {$deleted} monitoring records older than {$days} days.");

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;

class PruneAuditLogs extends Command
{
    /**
     * @var string
     */
    protected $signature = 'audit:prune {--days= : Jumlah hari retensi; default dari config/audit.php}';

    /**
     * @var string
     */
    protected $description = 'Hapus audit log yang melewati masa retensi';

    public function handle(): int
    {
        $days = max(1, (int) ($this->option('days') ?: config('audit.retention_days', 365)));
        $cutoff = now()->subDays($days);
        $chunkSize = max(1, (int) config('audit.prune_chunk_size', 500));
        $deleted = 0;

        do {
            $ids = AuditLog::query()
                ->where('created_at', '<', $cutoff)
                ->orderBy('id')
                ->limit($chunkSize)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $deleted += AuditLog::query()->whereKey($ids)->delete();
        } while ($ids->count() === $chunkSize);

        $this->info("Audit log dihapus: {$deleted} baris (retensi {$days} hari).");

        return self::SUCCESS;
    }
}

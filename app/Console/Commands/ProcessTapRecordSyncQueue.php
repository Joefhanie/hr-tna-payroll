<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessTapRecordSyncQueue extends Command
{
    protected $signature = 'tap-records:process-queue {--limit=200}';
    protected $description = 'Process queued tap_records and sync them into attendance';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $rows = DB::table('tap_record_sync_queue')
            ->whereNull('processed_at')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($rows->isEmpty()) {
            $this->info('No queued tap records found.');
            return 0;
        }

        $service = app(\App\Services\TapRecordAttendanceService::class);
        $processed = 0;

        foreach ($rows as $row) {
            try {
                $tap = \App\Models\TapRecord::find($row->tap_record_id);
                if ($tap) {
                    $service->syncForTapRecord($tap);
                }
                DB::table('tap_record_sync_queue')->where('id', $row->id)->update(['processed_at' => now(), 'updated_at' => now()]);
                $processed++;
            } catch (\Throwable $e) {
                // Log and continue with next
                logger()->error('Failed to process tap_record queue id '.$row->id.': '.$e->getMessage());
            }
        }

        $this->info("Processed {$processed} queued tap records.");
        return 0;
    }
}

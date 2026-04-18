<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteExpiredTwoFactorData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'two-factor:delete-expired {--chunk=1000}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete expired OTP codes and trusted devices in chunks';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $chunkSize = (int) $this->option('chunk');

        $otpDeleted     = $this->deleteExpired('two_factor_codes', 'expires_at', $chunkSize);
        $devicesDeleted = $this->deleteExpired('trusted_devices', 'trusted_until', $chunkSize);

        if ($this->output->isVerbose()) {
            $this->info("OTP codes deleted: {$otpDeleted}");
            $this->info("Trusted devices deleted: {$devicesDeleted}");
        }

        return Command::SUCCESS;
    }

    private function deleteExpired(string $table, string $column, int $chunkSize): int
    {
        $totalDeleted = 0;

        do {
            $count = DB::table($table)
                ->where($column, '<', now())
                ->limit($chunkSize)
                ->delete();

            $totalDeleted += $count;

        } while ($count > 0);

        return $totalDeleted;
    }
}

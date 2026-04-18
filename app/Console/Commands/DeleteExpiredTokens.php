<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteExpiredTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokens:delete-expired {--chunk=1000}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete expired personal access tokens in chunks';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $chunkSize    = (int) $this->option('chunk');
        $totalDeleted = 0;

        do {
            $count = DB::table('personal_access_tokens')
                ->whereNotNull('expires_at')
                ->where('expires_at', '<', now())
                ->limit($chunkSize)
                ->delete();

            $totalDeleted += $count;

        } while ($count > 0);

        if ($this->output->isVerbose()) {
            $this->info("Expired tokens deleted: {$totalDeleted}");
        }

        return Command::SUCCESS;
    }
}

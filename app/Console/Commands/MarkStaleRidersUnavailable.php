<?php

namespace App\Console\Commands;

use App\Enums\RiderAvailability;
use App\Models\RiderProfile;
use Illuminate\Console\Command;

class MarkStaleRidersUnavailable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:mark-stale-riders-unavailable';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Marks riders as unavailable if they have not sent a location update in the last 10 minutes.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $updated = RiderProfile::where('rider_availability', RiderAvailability::AVAILABLE)
            ->where('last_location_updated_at', '<', now()->subMinutes(10))
            ->update(['rider_availability' => RiderAvailability::UNAVAILABLE]);

        $this->info("Marked {$updated} stale rider(s) as unavailable.");
    }
}

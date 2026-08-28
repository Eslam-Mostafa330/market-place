<?php

namespace App\Console\Commands;

use App\Services\Support\SupportTicketService;
use Illuminate\Console\Command;

class CloseStaleSupportTickets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'support:close-stale-tickets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Closes resolved support tickets the customer has not returned to.';

    /**
     * Execute the console command.
     */
    public function handle(SupportTicketService $supportTicketService): void
    {
        $closed = $supportTicketService->closeStaleResolvedTickets();

        $this->info("Closed {$closed} stale support ticket(s).");
    }
}

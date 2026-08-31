<?php

namespace App\Console\Commands;

use App\Services\Support\SupportPresenceService;
use App\Services\Support\SupportTicketService;
use Illuminate\Console\Command;

class SweepSupportTickets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'support:sweep-tickets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tidies the desk: hands back stranded tickets, resolves then closes conversations nobody came back to, and settles whether anyone is still on shift.';

    /**
     * Execute the console command.
     */
    public function handle(SupportTicketService $tickets, SupportPresenceService $presence): void
    {
        $this->info(sprintf(
            'Resolved %d, handed back %d, closed %d.',
            $tickets->resolveAbandonedConversations(),
            $tickets->releaseTicketsFromAbsentAgents(),
            $tickets->closeStaleResolvedTickets(),
        ));

        $presence->availability();
    }
}

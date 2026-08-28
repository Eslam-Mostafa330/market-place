<?php

/*
* Settings for the customer support desk, agent presence and ticket limits.
*/
return [
    /*
     * How long an agent's last heartbeat keeps counting as presence. Agents ping
     * while their console is open, so anything older means the tab is gone.
     */
    'agent_presence_ttl_seconds' => env('SUPPORT_AGENT_PRESENCE_TTL', 120),

    /*
     * How many tickets a customer may have running at once, which stops a single
     * account from flooding the queue.
     */
    'max_open_tickets_per_customer' => 3,

    /*
     * Length ceiling for a single chat message.
     */
    'message_max_length' => 2000,

    /*
     * How many messages a ticket page returns at a time.
     */
    'messages_per_page' => 30,

    /*
     * How long a resolved ticket waits for the customer to come back before the
     * desk closes it for good.
     */
    'auto_close_resolved_after_hours' => env('SUPPORT_AUTO_CLOSE_HOURS', 48),
];

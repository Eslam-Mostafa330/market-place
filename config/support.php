<?php

/*
 * Support desk, agent presence and ticket limits.
 */
return [
    /*
     * How long an agent remains active based on their last request.
     */
    'agent_presence_ttl_minutes' => env('SUPPORT_AGENT_PRESENCE_TTL_MINUTES', 15),

    /*
     * How often an agent's ordinary requests are allowed to write a heartbeat.
     * Well under the presence window above, and far above one per request.
     */
    'heartbeat_write_every_minutes' => 1,

    /*
     * Maximum number of open tickets per customer.
     */
    'max_open_tickets_per_customer' => 3,

    /*
     * Maximum length of a chat message.
     */
    'message_max_length' => 2000,

    /*
     * Number of messages returned per ticket page.
     */
    'messages_per_page' => 30,

    /*
     * How long a resolved ticket stays open before being closed.
     */
    'auto_close_resolved_after_hours' => env('SUPPORT_AUTO_CLOSE_HOURS', 48),

    /*
     * How long to wait for a customer reply after the desk responds.
     */
    'abandoned_after_minutes' => env('SUPPORT_ABANDONED_MINUTES', 60),
];

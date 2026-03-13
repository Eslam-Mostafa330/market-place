<?php

use Illuminate\Support\Facades\Schedule;


Schedule::command('tokens:delete-expired')->daily();
Schedule::command('two-factor:delete-expired')->daily();
Schedule::command('app:mark-stale-riders-unavailable')->everyTenMinutes();
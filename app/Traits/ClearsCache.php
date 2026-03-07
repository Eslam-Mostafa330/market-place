<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

trait ClearsCache
{
    public function clearAdminSummaryCache($adminId)
    {
        Cache::forget("admin_summary_{$adminId}");
    }
}
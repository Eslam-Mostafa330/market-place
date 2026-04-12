<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Enums\SettingKey;
use App\Http\Controllers\Api\BaseApiController;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class AppInfoController extends BaseApiController
{
    public function __invoke(): JsonResponse
    {
        $data = Cache::rememberForever('public_settings', function () {
            return Setting::whereIn('key', SettingKey::publicCases())
                ->pluck('value', 'key')
                ->mapWithKeys(fn ($value, $key) => [
                    SettingKey::from($key)->key() => $value
                ]);
        });

        return $this->apiResponseShow($data);
    }
}
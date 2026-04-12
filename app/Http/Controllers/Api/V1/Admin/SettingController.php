<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\SettingKey;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Admin\Settings\UpdateContactSettingsRequest;
use App\Http\Requests\Admin\Settings\UpdateLoyaltyPointSettingsRequest;
use App\Http\Requests\Admin\Settings\UpdateSocialSettingsRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class SettingController extends BaseApiController
{
    /* ----- Loyalty Points ----- */
    public function showLoyaltyPoints(): JsonResponse
    {
        $loyaltyPoints = (int) Setting::where('key', SettingKey::LOYALTY_POINTS)->value('value');
        return $this->apiResponseShow($loyaltyPoints);
    }

    public function updateLoyaltyPoints(UpdateLoyaltyPointSettingsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $data = [
            ['key' => SettingKey::LOYALTY_POINTS, 'value' => $validated['loyalty_points'] ? (int) $validated['loyalty_points'] : null],
        ];

        Setting::upsert($data, ['key'], ['value']);
        Cache::forget('public_settings');

        $updatedLoyaltyPoints = (int) Setting::where('key', SettingKey::LOYALTY_POINTS)->value('value');
        return $this->apiResponseUpdated($updatedLoyaltyPoints);
    }

    /* ----- Contact Settings ----- */
    public function showContact(): JsonResponse
    {
        $data = $this->getSettings(SettingKey::contactCases());
        return $this->apiResponseShow($data);
    }

    public function updateContact(UpdateContactSettingsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $data = [
            ['key' => SettingKey::EMAIL, 'value' => $validated['email'] ?? null],
            ['key' => SettingKey::PHONE, 'value' => $validated['phone'] ?? null],
            ['key' => SettingKey::WHATSAPP, 'value' => $validated['whatsapp'] ?? null],
        ];

        Setting::upsert($data, ['key'], ['value']);
        Cache::forget('public_settings');

        $updatedContact = $this->getSettings(SettingKey::contactCases());
        return $this->apiResponseUpdated($updatedContact);
    }

    /* ----- Social Settings ----- */
    public function showSocial(): JsonResponse
    {
        $data = $this->getSettings(SettingKey::socialCases());
        return $this->apiResponseShow($data);
    }

    public function updateSocial(UpdateSocialSettingsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $data = [
            ['key' => SettingKey::FACEBOOK, 'value' => $validated['facebook'] ?? null],
            ['key' => SettingKey::INSTAGRAM, 'value' => $validated['instagram'] ?? null],
            ['key' => SettingKey::X, 'value' => $validated['x'] ?? null],
        ];

        Setting::upsert($data, ['key'], ['value']);
        Cache::forget('public_settings');

        $updatedSocial = $this->getSettings(SettingKey::socialCases());
        return $this->apiResponseUpdated($updatedSocial);
    }

    /**
     * Retrieve settings values by their keys.
     *
     * This method fetches the requested settings and returns them as an associative array,
     * using the key name defined in the SettingKey enum.
     *
     * @param array $keys Array of setting keys to retrieve
     * @return array Associative array
     */
    private function getSettings(array $keys): array
    {
        return Setting::whereIn('key', $keys)
            ->pluck('value', 'key')
            ->mapWithKeys(fn ($value, $key) => [
                SettingKey::from($key)->key() => $value
            ])
            ->toArray();
    }
}
<?php

namespace App\Models;

use DateTimeInterface;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'token',
        'abilities',
        'expires_at',
        'session_id',
        'tokenable_id',
        'tokenable_type',
    ];

    /**
     * Extended version of Sanctum's native createToken.
     *
     * Sanctum's HasApiTokens::createToken() does not support session_id,
     * so this method mirrors the original signature exactly and adds
     * session_id as an optional parameter to group paired access and
     * refresh tokens under the same session. This allows clean logout
     * (deleting both tokens at once) and active session tracking.
     *
     * @param  User                   $user        The user the token belongs to
     * @param  string                 $name        Token name e.g. 'AccessToken' or 'RefreshToken'
     * @param  array                  $abilities   Sanctum abilities assigned to this token
     * @param  DateTimeInterface|null $expiresAt   Expiry timestamp
     * @param  string|null            $sessionId   UUID shared between access and refresh token pair
     */
    public static function createWithSession(User $user, string $name, array $abilities = ['*'], ?DateTimeInterface $expiresAt = null, ?string $sessionId = null): NewAccessToken 
    {
        $plainTextToken = $user->generateTokenString();

        $token = $user->tokens()->create([
            'name'       => $name,
            'token'      => hash('sha256', $plainTextToken),
            'abilities'  => $abilities,
            'expires_at' => $expiresAt,
            'session_id' => $sessionId,
        ]);

        return new NewAccessToken($token, $token->getKey().'|'.$plainTextToken);
    }
}
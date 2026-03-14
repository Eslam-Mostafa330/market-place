<?php

namespace App\Http\Middleware;

use App\Enums\VendorVerificationStatus;
use App\Models\VendorProfile;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVendorIsVerifiedMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $profile = VendorProfile::where('user_id', auth()->id())->first();

        abort_if(
            ! $profile || $profile->verification_status !== VendorVerificationStatus::VERIFIED,
            403,
            __('auth.vendor_not_verified')
        );

        return $next($request);
    }
}
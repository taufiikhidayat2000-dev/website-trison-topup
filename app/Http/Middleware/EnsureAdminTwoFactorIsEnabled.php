<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Backend (admin/superadmin) accounts must have two-factor authentication
 * confirmed before they can use the CMS. Newly created admin accounts and
 * any pre-existing admin who hasn't set it up yet are redirected to the
 * two-factor settings page until they finish scanning the QR code and
 * confirming a code from their authenticator app.
 */
class EnsureAdminTwoFactorIsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole(['superadmin', 'admin'])) {
            return $next($request);
        }

        if ($user->hasEnabledTwoFactorAuthentication()) {
            return $next($request);
        }

        if ($request->routeIs('two-factor.*', 'password.confirm*', 'password.confirmation', 'logout')) {
            return $next($request);
        }

        return redirect()->route('two-factor.show')->with(
            'warning',
            'Akun admin wajib mengaktifkan two-factor authentication sebelum melanjutkan.'
        );
    }
}

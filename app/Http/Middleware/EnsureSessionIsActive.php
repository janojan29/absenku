<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSessionIsActive
{
    /**
     * Log out authenticated users after a period of inactivity.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $timeoutMinutes = (int) config('session.inactivity_timeout', 20);
        $lastActivityAt = $request->session()->get('last_activity_at');

        if ($lastActivityAt !== null) {
            $inactiveSeconds = Carbon::parse($lastActivityAt)->diffInSeconds(now());

            if ($inactiveSeconds > ($timeoutMinutes * 60)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Session expired due to inactivity.',
                    ], 401);
                }

                return redirect()->route('login')->withErrors([
                    'login_identifier' => 'Sesi berakhir karena tidak ada aktivitas lebih dari 20 menit. Silakan login kembali.',
                ]);
            }
        }

        $request->session()->put('last_activity_at', now()->toDateTimeString());

        return $next($request);
    }
}

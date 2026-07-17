<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request and add security headers.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (method_exists($response, 'header')) {
            // Anti Clickjacking
            $response->header('X-Frame-Options', 'DENY');
            
            // Anti MIME-Sniffing
            $response->header('X-Content-Type-Options', 'nosniff');
            
            // Anti XSS (Cross-Site Scripting)
            $response->header('X-XSS-Protection', '1; mode=block');
            
            // Force HTTPS & Anti Downgrade (HSTS)
            $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
            
            // Relaxed Content Security Policy for Livewire & Vite
            $response->header('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline' fonts.googleapis.com; img-src 'self' data: https:; font-src 'self' data: fonts.gstatic.com; connect-src 'self' ws: wss:;");
            
            // Referrer Policy
            $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');

            // Hapus header yang membocorkan teknologi server
            $response->headers->remove('X-Powered-By');
        }

        return $response;
    }
}

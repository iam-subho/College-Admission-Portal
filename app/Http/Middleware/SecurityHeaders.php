<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Appends security headers to every web response. Configured via config/security.php.
 * Add to the web middleware group in bootstrap/app.php.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $cfg = config('security.headers');

        $response->headers->set('X-Frame-Options', (string) $cfg['x_frame_options']);
        $response->headers->set('X-Content-Type-Options', (string) $cfg['x_content_type_options']);
        $response->headers->set('Referrer-Policy', (string) $cfg['referrer_policy']);
        $response->headers->set('Permissions-Policy', (string) $cfg['permissions_policy']);

        // HSTS only on HTTPS requests AND when explicitly enabled.
        if (! empty($cfg['hsts_enabled']) && $request->secure()) {
            $hsts = 'max-age='.((int) $cfg['hsts_max_age']);
            if ($cfg['hsts_include_subdomains']) {
                $hsts .= '; includeSubDomains';
            }
            if ($cfg['hsts_preload']) {
                $hsts .= '; preload';
            }
            $response->headers->set('Strict-Transport-Security', $hsts);
        }

        // Content Security Policy
        $cspCfg = config('security.csp');
        if (! empty($cspCfg['enabled']) && ! empty($cspCfg['directives'])) {
            $policy = implode('; ', $cspCfg['directives']);
            $header = $cspCfg['report_only']
                ? 'Content-Security-Policy-Report-Only'
                : 'Content-Security-Policy';
            $response->headers->set($header, $policy);
        }

        return $response;
    }
}

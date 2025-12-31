<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function boot(): void
    {
        parent::boot();

        // Configure trusted proxies for ngrok/reverse proxies
        // In dev environment, trust all proxies (for ngrok compatibility)
        if ($this->getEnvironment() === 'dev') {
            // Trust all proxies in dev (required for ngrok)
            // WARNING: Only use this in development! In production, specify exact proxy IPs
            // We need to trust the remote address, so we get it from the request
            // For ngrok, we trust all IPs by using the REMOTE_ADDR from the current request
            $trustedProxies = [];
            if (isset($_SERVER['REMOTE_ADDR'])) {
                $trustedProxies[] = $_SERVER['REMOTE_ADDR'];
            }
            // Also trust localhost
            $trustedProxies[] = '127.0.0.1';
            $trustedProxies[] = '::1';
            
            Request::setTrustedProxies(
                $trustedProxies,
                Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_HOST
            );
        }
    }
}

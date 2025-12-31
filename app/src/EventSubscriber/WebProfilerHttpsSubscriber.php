<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Force HTTPS URLs for Web Debug Toolbar when behind a proxy (e.g., ngrok)
 * 
 * This subscriber ensures that when behind a reverse proxy like ngrok,
 * Symfony correctly detects HTTPS and generates HTTPS URLs for the profiler.
 */
class WebProfilerHttpsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private KernelInterface $kernel
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 100],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // In dev environment, configure trusted proxies for ngrok
        if ($this->kernel->getEnvironment() === 'dev' && $request->headers->has('X-Forwarded-Proto')) {
            // Trust the current request's remote address (ngrok proxy)
            $remoteAddr = $request->server->get('REMOTE_ADDR');
            if ($remoteAddr) {
                Request::setTrustedProxies(
                    [$remoteAddr, '127.0.0.1', '::1'],
                    Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_HOST
                );
            }
        }

        // Check if we're behind a proxy (ngrok, etc.) with HTTPS
        if ($request->headers->has('X-Forwarded-Proto') && 
            $request->headers->get('X-Forwarded-Proto') === 'https') {
            // Force HTTPS detection by setting server variables
            $request->server->set('HTTPS', 'on');
            $request->server->set('SERVER_PORT', 443);
            $request->server->set('REQUEST_SCHEME', 'https');
        }
    }
}


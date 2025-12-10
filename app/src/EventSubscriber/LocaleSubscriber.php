<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleSubscriber implements EventSubscriberInterface
{
    private string $defaultLocale;

    public function __construct(string $defaultLocale = 'en')
    {
        $this->defaultLocale = $defaultLocale;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Try to get locale from session
        if (!$request->hasPreviousSession()) {
            return;
        }

        // If the locale is set in the URL, use it and save it to session
        if ($locale = $request->query->get('_locale')) {
            $request->getSession()->set('_locale', $locale);
            $request->setLocale($locale);
        } elseif ($locale = $request->attributes->get('_locale')) {
            // Locale from route parameter
            $request->getSession()->set('_locale', $locale);
        } elseif ($locale = $request->getSession()->get('_locale')) {
            // Use locale from session
            $request->setLocale($locale);
        } else {
            // Use default locale
            $request->setLocale($this->defaultLocale);
        }
    }
}


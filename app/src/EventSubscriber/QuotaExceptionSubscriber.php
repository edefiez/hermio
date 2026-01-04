<?php

namespace App\EventSubscriber;

use App\Exception\QuotaExceededException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class QuotaExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 10],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof QuotaExceededException) {
            return;
        }

        $messageData = $exception->getMessageData();
        $message = sprintf(
            'You have reached your quota limit of %d card(s). You currently have %d card(s).',
            $messageData['limit'] ?? 0,
            $messageData['current_usage'] ?? 0
        );

        // Add upgrade suggestion based on plan
        if (isset($messageData['plan'])) {
            if ($messageData['plan'] === 'Free') {
                $message .= ' Upgrade to Pro (10 cards) or Enterprise (unlimited) to create more cards.';
            } elseif ($messageData['plan'] === 'Pro') {
                $message .= ' Upgrade to Enterprise (unlimited) to create more cards.';
            }
        }

        $event->getRequest()->getSession()->getFlashBag()->add(
            'error',
            $message
        );

        // Redirect to My Plan page
        $response = new RedirectResponse(
            $this->urlGenerator->generate('app_account_my_plan')
        );
        $event->setResponse($response);
    }
}


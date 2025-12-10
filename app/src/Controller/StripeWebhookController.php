<?php

namespace App\Controller;

use App\Service\StripeWebhookService;
use Psr\Log\LoggerInterface;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/stripe/webhook')]
class StripeWebhookController extends AbstractController
{
    public function __construct(
        private StripeWebhookService $webhookService,
        private LoggerInterface $logger,
        private string $webhookSecret
    ) {
    }

    #[Route('', name: 'app_stripe_webhook', methods: ['POST'])]
    public function handleWebhook(Request $request): Response
    {
        $payload = $request->getContent();
        $signature = $request->headers->get('Stripe-Signature');

        if (!$signature) {
            $this->logger->warning('Stripe webhook received without signature header');
            return new Response('Missing signature', Response::HTTP_BAD_REQUEST);
        }

        try {
            // Verify webhook signature
            $event = Webhook::constructEvent($payload, $signature, $this->webhookSecret);
        } catch (\UnexpectedValueException $e) {
            $this->logger->error('Invalid webhook payload', ['error' => $e->getMessage()]);
            return new Response('Invalid payload', Response::HTTP_BAD_REQUEST);
        } catch (SignatureVerificationException $e) {
            $this->logger->warning('Invalid webhook signature', ['error' => $e->getMessage()]);
            return new Response('Invalid signature', Response::HTTP_BAD_REQUEST);
        }

        try {
            // Process the event
            $this->webhookService->processEvent($event);
            
            // Always return 200 OK to prevent Stripe retries
            return new Response('Webhook processed', Response::HTTP_OK);
        } catch (\Exception $e) {
            // Log error but return 200 OK to prevent infinite retries
            $this->logger->error('Error processing webhook event', [
                'event_id' => $event->id ?? 'unknown',
                'event_type' => $event->type ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Return 200 OK even on error to prevent Stripe retries
            // Failed events are logged and can be manually reconciled
            return new Response('Webhook processing error logged', Response::HTTP_OK);
        }
    }
}


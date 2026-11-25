<?php

namespace TakahiroNago\AppFoundation\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Exception\UnexpectedValueException;
use Stripe\Webhook;
use TakahiroNago\AppFoundation\Contracts\HandlesStripeWebhooks;

/**
 * Verifies the Stripe signature with `services.stripe.webhook_secret` and hands the event to the
 * application's handler. The route must be excluded from CSRF verification.
 */
class StripeWebhookController
{
    public function __invoke(Request $request, HandlesStripeWebhooks $handler): Response
    {
        $webhookSecret = config('services.stripe.webhook_secret');
        if (! is_string($webhookSecret) || $webhookSecret === '') {
            abort(503, 'Stripe webhook is not configured.');
        }

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
                $webhookSecret,
            );
        } catch (SignatureVerificationException|UnexpectedValueException) {
            abort(400, 'Invalid Stripe webhook signature or payload.');
        }

        $handler->handleWebhook($event);

        return response('Webhook received.', 200);
    }
}

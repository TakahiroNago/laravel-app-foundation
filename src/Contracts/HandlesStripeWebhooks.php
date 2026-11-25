<?php

namespace TakahiroNago\AppFoundation\Contracts;

use Stripe\Event;

/**
 * Implemented by the application's billing service and bound in its service provider. The
 * event has already passed signature verification when it arrives here.
 */
interface HandlesStripeWebhooks
{
    public function handleWebhook(Event $event): void;
}

<?php

namespace TakahiroNago\AppFoundation\Tests\Fixtures;

use Stripe\Event;
use TakahiroNago\AppFoundation\Contracts\HandlesStripeWebhooks;

class RecordingWebhookHandler implements HandlesStripeWebhooks
{
    /**
     * @var list<Event>
     */
    public array $events = [];

    public function handleWebhook(Event $event): void
    {
        $this->events[] = $event;
    }
}

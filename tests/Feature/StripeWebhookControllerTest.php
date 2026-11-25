<?php

use TakahiroNago\AppFoundation\Contracts\HandlesStripeWebhooks;
use TakahiroNago\AppFoundation\Tests\Fixtures\RecordingWebhookHandler;

beforeEach(function () {
    $this->handler = new RecordingWebhookHandler;
    $this->app->instance(HandlesStripeWebhooks::class, $this->handler);
    config(['services.stripe.webhook_secret' => 'whsec_test_secret']);
});

function signedStripeHeader(string $payload, string $secret, ?int $timestamp = null): string
{
    $timestamp ??= time();

    return 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$payload, $secret);
}

function postStripeWebhook(string $payload, string $signature)
{
    return test()->call('POST', '/stripe/webhook', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => $signature,
        'CONTENT_TYPE' => 'application/json',
    ], $payload);
}

it('passes a correctly signed event to the application handler', function () {
    $payload = json_encode(['id' => 'evt_123', 'object' => 'event', 'type' => 'checkout.session.completed']);

    postStripeWebhook($payload, signedStripeHeader($payload, 'whsec_test_secret'))->assertOk();

    expect($this->handler->events)->toHaveCount(1)
        ->and($this->handler->events[0]->id)->toBe('evt_123');
});

it('rejects a payload signed with another secret', function () {
    $payload = json_encode(['id' => 'evt_123', 'object' => 'event']);

    postStripeWebhook($payload, signedStripeHeader($payload, 'whsec_other'))->assertStatus(400);

    expect($this->handler->events)->toBeEmpty();
});

it('rejects a payload that was changed after signing', function () {
    $payload = json_encode(['id' => 'evt_123', 'object' => 'event']);
    $signature = signedStripeHeader($payload, 'whsec_test_secret');

    postStripeWebhook(str_replace('evt_123', 'evt_999', $payload), $signature)->assertStatus(400);

    expect($this->handler->events)->toBeEmpty();
});

it('rejects a request without a signature', function () {
    postStripeWebhook('{}', '')->assertStatus(400);
});

it('answers 503 while the webhook secret is not configured', function () {
    config(['services.stripe.webhook_secret' => null]);
    $payload = '{}';

    postStripeWebhook($payload, signedStripeHeader($payload, 'whsec_test_secret'))->assertStatus(503);

    expect($this->handler->events)->toBeEmpty();
});

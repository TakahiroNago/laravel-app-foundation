<?php

use Illuminate\Support\Facades\DB;
use TakahiroNago\AppFoundation\Services\GuestTrialCreditService;

it('consumes credits up to the configured limit', function () {
    $credits = app(GuestTrialCreditService::class);
    $device = str_repeat('a', 64);

    expect($credits->remaining($device))->toBe(2)
        ->and($credits->consume($device))->toBeTrue()
        ->and($credits->consume($device))->toBeTrue()
        ->and($credits->consume($device))->toBeFalse()
        ->and($credits->remaining($device))->toBe(0);
});

it('counts each device separately', function () {
    $credits = app(GuestTrialCreditService::class);

    $credits->consume('device-one');
    $credits->consume('device-one');

    expect($credits->remaining('device-two'))->toBe(2);
});

it('gives a credit back on refund and never goes below zero used', function () {
    $credits = app(GuestTrialCreditService::class);
    $device = 'device-refund';

    $credits->refund($device);
    expect($credits->remaining($device))->toBe(2);

    $credits->consume($device);
    $credits->refund($device);
    $credits->refund($device);

    expect($credits->remaining($device))->toBe(2);
});

it('stores the device token only as a keyed hash', function () {
    app(GuestTrialCreditService::class)->consume('plain-device-token');

    $stored = DB::table('guest_trial_credits')->value('device_hash');

    expect($stored)->not->toContain('plain-device-token')
        ->and($stored)->toBe(hash_hmac('sha256', 'plain-device-token', config('app.key')));
});

it('reads the limit from configuration', function () {
    config(['app-foundation.guest_trial.credit_limit' => 1]);
    $credits = app(GuestTrialCreditService::class);

    expect($credits->consume('device-limit'))->toBeTrue()
        ->and($credits->consume('device-limit'))->toBeFalse();
});

it('refuses to work without an application key', function () {
    config(['app.key' => '']);

    app(GuestTrialCreditService::class)->remaining('device');
})->throws(RuntimeException::class);

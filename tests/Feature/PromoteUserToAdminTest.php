<?php

use TakahiroNago\AppFoundation\Tests\Fixtures\User;

it('promotes an active user', function () {
    $user = User::create(['email' => 'owner@example.com', 'is_active' => true]);

    $this->artisan('users:promote-admin', ['email' => 'owner@example.com'])->assertSuccessful();

    expect($user->fresh()->isAdmin())->toBeTrue();
});

it('fails for an unknown email address', function () {
    $this->artisan('users:promote-admin', ['email' => 'nobody@example.com'])->assertFailed();
});

it('does not promote a suspended user', function () {
    $user = User::create(['email' => 'suspended@example.com', 'is_active' => false]);

    $this->artisan('users:promote-admin', ['email' => 'suspended@example.com'])->assertFailed();

    expect($user->fresh()->isAdmin())->toBeFalse();
});

it('reports a user model that cannot be an administrator', function () {
    config(['auth.providers.users.model' => Illuminate\Foundation\Auth\User::class]);

    $this->artisan('users:promote-admin', ['email' => 'owner@example.com']);
})->throws(LogicException::class);

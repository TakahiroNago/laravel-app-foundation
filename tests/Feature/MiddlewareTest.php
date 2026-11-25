<?php

use TakahiroNago\AppFoundation\Tests\Fixtures\User;

it('lets guests and active users through', function () {
    $this->get('/members')->assertOk();

    $this->actingAs(User::create(['email' => 'active@example.com', 'is_active' => true]))
        ->get('/members')
        ->assertOk();
});

it('signs a suspended user out and sends them to the login page', function () {
    $user = User::create(['email' => 'suspended@example.com', 'is_active' => false]);

    $this->actingAs($user)
        ->get('/members')
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('allows only administrators into admin routes', function () {
    $this->get('/admin')->assertForbidden();

    $this->actingAs(User::create(['email' => 'member@example.com', 'is_active' => true]))
        ->get('/admin')
        ->assertForbidden();

    $this->actingAs(User::create(['email' => 'admin@example.com', 'is_active' => true, 'is_admin' => true]))
        ->get('/admin')
        ->assertOk();
});

it('refuses a signed-in user whose model is not an Administrator', function () {
    $user = new Illuminate\Foundation\Auth\User;
    $user->forceFill(['id' => 99, 'is_active' => true]);

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

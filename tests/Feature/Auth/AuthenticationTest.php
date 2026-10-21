<?php

use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('users are redirected to redirect parameter after login', function () {
    $user = User::factory()->create();

    $targetUrl = '/some/custom/target-page';

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
        'redirect' => $targetUrl,
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect($targetUrl);
});

test('login screen preserves redirect query param in form', function () {
    $targetUrl = 'http://localhost/attendance/check-in/sample-qr-token';

    $response = $this->get('/login?redirect=' . urlencode($targetUrl));

    $response->assertStatus(200);
    $response->assertSee('name="redirect"', false);
    $response->assertSee(e($targetUrl), false);
});


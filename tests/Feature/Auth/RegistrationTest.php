<?php

use App\Models\User;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'email' => 'test@example.com',
        'mobile_number' => '09123456781',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    expect(auth()->user()->hasRole(User::ROLE_END_USER))->toBeTrue();
    $response->assertRedirect(route('dashboard', absolute: false));
});

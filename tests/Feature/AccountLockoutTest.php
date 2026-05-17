<?php

use App\Models\User;

test('account is locked after five failed login attempts', function () {
    $user = User::factory()->create([
        'password' => bcrypt('correct-horse'),
    ]);

    // Five wrong attempts. Use a different IP each time so the throttle key
    // doesn't IP-rate-limit us first; we want to prove the per-account lock works.
    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ], ['REMOTE_ADDR' => '10.0.0.'.($i + 1)]);
    }

    $user->refresh();

    expect($user->locked_until)->not->toBeNull();
    expect($user->login_attempts)->toBeGreaterThanOrEqual(5);

    // Even with the correct password the account stays locked.
    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'correct-horse',
    ], ['REMOTE_ADDR' => '10.0.0.99']);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('successful login resets login attempts and stamps last login', function () {
    $user = User::factory()->create([
        'password' => bcrypt('correct-horse'),
        'login_attempts' => 3,
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'correct-horse',
    ]);

    $response->assertRedirect();
    $user->refresh();

    expect($user->login_attempts)->toBe(0);
    expect($user->locked_until)->toBeNull();
    expect($user->last_login_at)->not->toBeNull();
});

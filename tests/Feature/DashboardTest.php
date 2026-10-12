<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('authenticated users can switch appearance from the sidebar', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-test="appearance-toggle"', false)
        ->assertSee('$flux.dark = ! $flux.dark', false);
});

test('the public landing page uses the shared brand mark', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('data-brand-mark', false)
        ->assertSee('favicon.svg', false);
});

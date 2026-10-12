<?php

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

test('database seeding creates a verified administrator and prints a temporary password', function () {
    config(['resistance-assessment.admin_emails' => ['admin@antimalaria.com']]);

    Artisan::call('db:seed');

    $output = Artisan::output();
    $admin = User::query()->where('email', 'admin@antimalaria.com')->firstOrFail();
    preg_match('/TEMPORARY_ADMIN_PASSWORD=([^\r\n]+)/', $output, $matches);

    expect($admin->name)->toBe('Antimalaria Administrator')
        ->and($admin->email_verified_at)->not->toBeNull()
        ->and($matches)->toHaveCount(2)
        ->and(strlen($matches[1]))->toBe(48)
        ->and(ctype_xdigit($matches[1]))->toBeTrue()
        ->and(Hash::check($matches[1], $admin->password))->toBeTrue();

    $this->assertDatabaseCount('users', 1);

    $this->actingAs($admin)
        ->get(route('admin.rules.index'))
        ->assertOk();
});

test('database seeding does not reset or reveal an existing administrator password', function () {
    config(['resistance-assessment.admin_emails' => ['admin@antimalaria.com']]);
    $existingPassword = 'Existing-Admin-Password-123!';
    $admin = User::factory()->create([
        'email' => 'admin@antimalaria.com',
        'password' => Hash::make($existingPassword),
    ]);

    Artisan::call('db:seed');

    expect(Hash::check($existingPassword, $admin->fresh()->password))->toBeTrue()
        ->and(Artisan::output())->toContain('already exists')
        ->not->toContain('TEMPORARY_ADMIN_PASSWORD=');

    $this->assertDatabaseCount('users', 1);
});

test('administrator seeding is refused when the address is not allow-listed', function () {
    config(['resistance-assessment.admin_emails' => []]);

    expect(fn () => app(AdminUserSeeder::class)->run())
        ->toThrow(RuntimeException::class, 'Add admin@antimalaria.com to ANTIMALARIA_ADMIN_EMAILS');

    $this->assertDatabaseCount('users', 0);
});

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    private const ADMIN_EMAIL = 'admin@antimalaria.com';

    public function run(): void
    {
        if (! in_array(self::ADMIN_EMAIL, config('resistance-assessment.admin_emails', []), true)) {
            throw new RuntimeException('Add admin@antimalaria.com to ANTIMALARIA_ADMIN_EMAILS before seeding the administrator.');
        }

        $output = $this->command->getOutput();

        if (User::query()->where('email', self::ADMIN_EMAIL)->exists()) {
            $output->writeln('Admin account already exists; its password and verification status were not changed.');

            return;
        }

        $temporaryPassword = bin2hex(random_bytes(24));

        $admin = new User;
        $admin->name = 'Antimalaria Administrator';
        $admin->email = self::ADMIN_EMAIL;
        $admin->email_verified_at = Carbon::now();
        $admin->password = Hash::make($temporaryPassword);
        $admin->save();

        $output->writeln('TEMPORARY_ADMIN_PASSWORD='.$temporaryPassword);
    }
}

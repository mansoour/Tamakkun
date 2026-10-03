<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Fake demo accounts for local development only.
 * Every name here is invented; never put real student data in Git.
 * All demo passwords are "password" (see README).
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('DemoSeeder must never run in production.');
        }

        User::factory()->admin()->create([
            'name' => 'مديرة النظام (تجريبي)',
            'username' => 'admin',
            'email' => 'admin@tamakkun.test',
        ]);

        User::factory()->counselor()->create([
            'name' => 'الموجهة الطلابية (تجريبي)',
            'username' => 'counselor',
            'email' => 'counselor@tamakkun.test',
        ]);

        User::factory()->student()->create([
            'name' => 'طالبة تجريبية',
            'username' => 'student',
        ]);

        User::factory()->student()->withStatus(UserStatus::DISABLED)->create([
            'name' => 'طالبة بحساب معطّل (تجريبي)',
            'username' => 'disabled-student',
        ]);

        User::factory()->student()->count(10)->create();
    }
}

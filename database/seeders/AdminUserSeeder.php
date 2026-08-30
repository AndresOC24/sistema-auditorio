<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the single TI user that operates the panel.
     *
     * Idempotent, and deliberately conservative: the password is only written
     * when the account is created. Re-seeding an existing installation must
     * never reset a password the operator already changed.
     */
    public function run(): void
    {
        $user = User::firstOrNew([
            'email' => config('auditorio.admin.email'),
        ]);

        $user->name = config('auditorio.admin.name');

        if (! $user->exists) {
            $user->password = config('auditorio.admin.password');
        }

        $user->email_verified_at ??= now();

        $user->save();
    }
}

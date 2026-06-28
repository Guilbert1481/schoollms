<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Creates a test Principal account for Memory Ridge (school_id = 1).
 * Email: principal@memoryridge.test  | Password: password
 */
class PrincipalUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'principal@memoryridge.test';

        $existing = DB::table('users')->where('email', $email)->first();
        if ($existing) {
            DB::table('users')->where('id', $existing->id)->update([
                'role'       => 'principal',
                'school_id'  => 1,
                'updated_at' => now(),
            ]);
            $this->command?->info('Principal user already exists; role/school updated. id='.$existing->id);
            return;
        }

        $id = DB::table('users')->insertGetId([
            'first_name' => 'Memory',
            'last_name'  => 'Principal',
            'school_id'  => 1,
            'email'      => $email,
            'password'   => Hash::make('password'),
            'role'       => 'principal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command?->info("Principal user created: {$email} / password   (id={$id})");
    }
}

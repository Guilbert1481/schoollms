<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperadminSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'name' => 'Platform Owner',
            'email' => 'owner@yourplatform.com', // Use your real email
            'password' => Hash::make('your-secure-password'),
            'role' => 'superadmin',
            'school_id' => null, // Superadmins don't belong to a specific school
        ]);
    }
}
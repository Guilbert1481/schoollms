<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SchoolProfileSeeder extends Seeder
{
    public function run()
    {
        DB::table('school_profiles')->insert([
            [
                'id' => 1,
                'school_id' => 1,

                'school_name' => 'Memory Ridge International School and Colleges',
                'school_logo' => 'uploads/schools/memory_ridge_logo.png',
                'school_seal' => 'uploads/schools/memory_ridge_seal.png',
                'school_hero' => 'uploads/schools/memory_ridge_hero.jpg',

                'address' => '123 Memory Ridge Avenue, Diliman',
                'unit_number' => '29',
                'building' => 'Memory Ridge Building',
                'phase' => 'Phase 1',
                'street' => 'Jericho street',
                'barangay' => 'Barangay Moonwalk',
                'district' => '',
                'city' => 'Paranaque City',
                'province' => 'Metro Manila',
                'region' => 'NCR',
                'country' => 'Philippines',
                'zip_code' => '1702',

                'contact_number' => '0281234567',
                'mobile_number' => '09682555894',
                'fax_number' => '0287654321',
                'website' => 'https://memoryridge.edu.ph',
                'email' => 'admin@memoryridge.edu.ph',

                'motto' => 'Excellence Beyond Standards',
                'vision' => 'To be a premier institution shaping globally competitive leaders.',
                'mission' => 'To provide quality education, innovation, and leadership development.',

                'head_name' => 'Dr. Guilbert Llantos Jabinar',
                'head_title' => 'President',
                'registrar_name' => 'Jane Doe',
                'registrar_title' => 'Registrar',

                'established_year' => '2024',
                'business_type' => 'Corporation',

                'tax_number' => '123-456-789',
                'sss_number' => 'SSS-1234567',
                'business_permit_number' => 'BP-2024-0001',

                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
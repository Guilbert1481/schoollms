<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuoteSeeder extends Seeder
{
    public function run()
    {
        DB::table('quotes')->insert([
            [
                'content' => 'Success is not final, failure is not fatal: it is the courage to continue that counts.',
                'author' => 'Winston Churchill',
                'theme' => 'success',
                'is_active' => 1,
                'display_duration' => 10,
                'activated_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'content' => 'Don’t be afraid to give up the good to go for the great.',
                'author' => 'John D. Rockefeller',
                'theme' => 'success',
                'is_active' => 1,
                'display_duration' => 10,
                'activated_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'content' => 'Success usually comes to those who are too busy to be looking for it.',
                'author' => 'Henry David Thoreau',
                'theme' => 'success',
                'is_active' => 1,
                'display_duration' => 10,
                'activated_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'content' => 'Opportunities don’t happen. You create them.',
                'author' => 'Chris Grosser',
                'theme' => 'success',
                'is_active' => 1,
                'display_duration' => 10,
                'activated_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'content' => 'I find that the harder I work, the more luck I seem to have.',
                'author' => 'Thomas Jefferson',
                'theme' => 'success',
                'is_active' => 1,
                'display_duration' => 10,
                'activated_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'content' => 'Success is walking from failure to failure with no loss of enthusiasm.',
                'author' => 'Winston Churchill',
                'theme' => 'success',
                'is_active' => 1,
                'display_duration' => 10,
                'activated_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'content' => 'Try not to become a man of success. Rather become a man of value.',
                'author' => 'Albert Einstein',
                'theme' => 'success',
                'is_active' => 1,
                'display_duration' => 10,
                'activated_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'content' => 'The way to get started is to quit talking and begin doing.',
                'author' => 'Walt Disney',
                'theme' => 'success',
                'is_active' => 1,
                'display_duration' => 10,
                'activated_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'content' => 'Success is not how high you have climbed, but how you make a positive difference to the world.',
                'author' => 'Roy T. Bennett',
                'theme' => 'success',
                'is_active' => 1,
                'display_duration' => 10,
                'activated_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'content' => 'Dream big and dare to fail.',
                'author' => 'Norman Vaughan',
                'theme' => 'success',
                'is_active' => 1,
                'display_duration' => 10,
                'activated_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

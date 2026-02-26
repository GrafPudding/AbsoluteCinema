<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MovieSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('movies')->insert([
            [
                'title' => 'Interstellar',
                'duration_minutes' => 169,
                'description' => 'A team travels through a wormhole in space.',
                'poster_url' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'The Dark Knight',
                'duration_minutes' => 152,
                'description' => 'Batman faces the Joker.',
                'poster_url' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

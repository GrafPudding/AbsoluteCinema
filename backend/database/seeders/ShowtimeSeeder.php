<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Movie;

class ShowtimeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $movies = Movie::all();

        foreach ($movies as $movie) {
            DB::table('showtimes')->insert([
                [
                    'movie_id' => $movie->id,
                    'starts_at' => now()->addDay()->setTime(18, 0),
                    'auditorium' => 'A',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'movie_id' => $movie->id,
                    'starts_at' => now()->addDay()->setTime(20, 30),
                    'auditorium' => 'A',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'movie_id' => $movie->id,
                    'starts_at' => now()->addDays(2)->setTime(19, 0),
                    'auditorium' => 'A',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }
}
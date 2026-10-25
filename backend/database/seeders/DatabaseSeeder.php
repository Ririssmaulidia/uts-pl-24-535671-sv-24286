<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Aman dijalankan berulang kali (idempoten): pengguna demo dibuat sekali saja.
     * Email dan password demo dibaca dari environment (lihat .env.example).
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => env('SEED_USER_EMAIL', 'mahasiswa@example.com')],
            [
                'name' => 'Mahasiswa Demo',
                'password' => env('SEED_USER_PASSWORD', 'password123'),
            ],
        );

        if ($user->expenses()->doesntExist()) {
            $user->expenses()->createMany([
                ['title' => 'Makan siang', 'amount' => 15000, 'category' => 'Makan', 'spent_on' => now()->subDays(2)->toDateString(), 'note' => 'Kantin kampus'],
                ['title' => 'Bensin motor', 'amount' => 25000, 'category' => 'Transport', 'spent_on' => now()->subDay()->toDateString(), 'note' => null],
                ['title' => 'Fotokopi modul', 'amount' => 8000, 'category' => 'Kuliah', 'spent_on' => now()->toDateString(), 'note' => null],
            ]);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\QseInterlocutor;
use Illuminate\Database\Seeder;

class QseInterlocutorsSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['name' => 'Lydie Bernard', 'role' => 'Coordinatrice QSE', 'phone' => '06.22.83.61.71', 'is_main' => true, 'sort_order' => 1],
            ['name' => 'Nazim Belhadj-Abed', 'role' => 'Animateur QSE', 'phone' => '06.26.85.16.87', 'sort_order' => 2],
            ['name' => 'Morgane RAYNAUD', 'role' => 'Animatrice QSE', 'phone' => '07.87.09.86.90', 'sort_order' => 3],
            ['name' => 'Thomas DAMAREZ', 'role' => 'Responsable QSE', 'phone' => '06.13.70.32.85', 'is_main' => true, 'sort_order' => 4],
            ['name' => 'Ethan DELRUE', 'role' => 'Assistant QSE', 'phone' => '07.85.79.07.82', 'email' => 'qse@salti.fr', 'sort_order' => 5],
        ];

        foreach ($rows as $row) {
            QseInterlocutor::firstOrCreate(['name' => $row['name']], $row);
        }

        $this->command->info(count($rows).' interlocuteurs QSE seedés.');
    }
}

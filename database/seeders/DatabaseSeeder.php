<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Compte QSE central (voit tous les PDP)
        User::create([
            'name' => 'Service QSE',
            'email' => 'qse@salti.fr',
            'password' => Hash::make('changeme'),
            'role' => User::ROLE_QSE_ADMIN,
            'phone' => '06.13.70.32.85',
        ]);

        // Quelques agences SALTI de démonstration
        $agencies = [
            ['Agence Bordeaux', 'bordeaux@salti.fr', 'Bordeaux'],
            ['Agence Lyon', 'lyon@salti.fr', 'Lyon'],
            ['Agence Paris', 'paris@salti.fr', 'Paris'],
        ];

        foreach ($agencies as [$name, $email, $city]) {
            User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('changeme'),
                'role' => User::ROLE_AGENCY,
                'city' => $city,
            ]);
        }
    }
}

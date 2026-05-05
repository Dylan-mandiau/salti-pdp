<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder des vraies agences SALTI (extraites de l'annuaire interne).
 *
 * Mot de passe initial : "changeme" — à modifier IMMÉDIATEMENT pour chaque agence
 * via le panel /admin/agencies (Reset MDP) ou via tinker.
 *
 * Idempotent : utilise updateOrCreate par email, donc relançable sans casser
 * les comptes déjà créés (ne ré-écrit que les champs city/address/phone).
 *
 * Lancer : php artisan db:seed --class=AgenciesSeeder
 */
class AgenciesSeeder extends Seeder
{
    /**
     * Pool de mots BTP. Le mot de passe d'une agence est :
     *   <Mot BTP><2 chiffres aléatoires>@
     * Ex : Nacelle36@, Marteau12@, Echaff89@, etc.
     */
    private const BTP_WORDS = [
        'Nacelle', 'Marteau', 'Echaff', 'Casque', 'Truelle', 'Niveau', 'Brique',
        'Beton', 'Pelle', 'Foreuse', 'Visseuse', 'Madrier', 'Solive', 'Poutre',
        'Plinthe', 'Toiture', 'Gravier', 'Sable', 'Ciment', 'Boulon', 'Ecrou',
        'Tuyau', 'Rabot', 'Equerre', 'Burin', 'Massif', 'Tuile', 'Bardeau',
        'Charpente', 'Vasis', 'Lattis', 'Garde', 'Volet', 'Marche', 'Rampe',
        'Pioche', 'Truelle', 'Pince', 'Cle', 'Buse', 'Cable', 'Cavite',
        'Etagere', 'Latte', 'Cheville', 'Tirefond', 'Vis', 'Longeron', 'Joint',
        'Mastic', 'Lambris', 'Couverture',
    ];

    public function run(): void
    {
        // Format : [code, ville, email, adresse, telephone]
        $agencies = [
            ['AM', 'Amiens', 'amiens@salti.fr', "Allée Alain Ducamp, ZI Amiens Nord, 80000 Amiens", '03.22.50.25.25'],
            ['AR', 'Arras', 'arras@salti.fr', "Rue François Hennebicque, 62223 Saint Laurent Blangy", '03.21.21.74.74'],
            ['AV', 'Avignon', 'avignon@salti.fr', "84130 Le Pontet", '04.51.62.06.20'],
            ['BY', 'Bayonne', 'bayonne@salti.fr', "506 rue de l'Industrie, 40220 Tarnos", '05.59.46.00.00'],
            ['BV', 'Beauvais', 'beauvais@salti.fr', "7 rue Moulin de Bracheux, 60000 Beauvais", '03.44.22.80.00'],
            ['BE', 'Béthune', 'bethune@salti.fr', "Rue du Marais, ZI Pont du Réveillon, 62157 Allouagne", '03.21.65.45.45'],
            ['BX', 'Bordeaux', 'bordeaux@salti.fr', "216 Av du Maréchal Leclerc, 33130 Bègles", '05.57.22.02.20'],
            ['BL', 'Boulogne', 'boulogne@salti.fr', "33 Rd de la Liane, ZI, 62360 Saint Léonard", '03.21.31.99.00'],
            ['BO', 'Bourges', 'bourges@salti.fr', "15 allée Évariste Galois, 18000 Bourges", '02.59.58.10.10'],
            ['CN', 'Caen', 'caen@salti.fr', "9 rue du Bel Air, 14790 Verson", '02.31.08.24.24'],
            ['CA', 'Calais', 'calais@salti.fr', "Avenue de la Liberté, ZI Transmarck, 62730 Marck", '03.21.19.60.80'],
            ['CR', 'Cambrai', 'cambrai@salti.fr', "Zone Acropole de IA2, Av. des Deux Vallées, 59554 Raillencourt Ste Olle", '03.27.70.90.00'],
            ['CF', 'Clermont Ferrand', 'clermontferrand@salti.fr', "18 Rue Gutenberg, 63100 Clermont Ferrand", '04.73.14.24.54'],
            ['DJ', 'Dijon', 'dijon@salti.fr', "21 rue de Malines, 21000 Dijon", '03.80.50.00.04'],
            ['DO', 'Douai', 'douai@salti.fr', "109/110 rue Maurice Cauvery, ZI Douai Dorignies, 59500 Douai", '03.27.96.01.99'],
            ['DK', 'Dunkerque', 'dunkerque@salti.fr', "Rue Louis Blanqui, ZI, 59760 Grande-Synthe", '03.28.64.30.40'],
            ['GB', 'Grenoble', 'grenoble@salti.fr', "150 allée du Sautaret, 38113 Veurey-Voroize", '04.38.02.00.00'],
            ['LA', 'Lagny-le-Sec', 'lagny@salti.fr', "2 rue de la Paix, 60330 Lagny-le-Sec", '03.44.54.14.14'],
            ['HA', 'Le Havre', 'lehavre@salti.fr', "22 avenue Marcel le Mignot, 76700 Gonfreville L'Orcher", '02.35.47.47.47'],
            ['MN', 'Le Mans', 'lemans@salti.fr', "ZI Sud, Route d'Allonnes, 72100 Le Mans", '02.43.85.20.00'],
            ['LS', 'Lens', 'lens@salti.fr', "Rue de l'Abbé Jerzy Popieluszko, ZI Lens Nord, 62300 Lens", '03.21.42.50.50'],
            ['LI', 'Limoges', 'limoges@salti.fr', "33-37 Rue du Châtenet, 87410 Le Palais-sur-Vienne", '05.25.62.34.62'],
            ['OM', 'Lomme Bases Vie', 'bvlomme@salti.fr', "2 quater rue de l'Europe, 59160 Lomme", '03.20.50.70.00'],
            ['LO', 'Lomme', 'lomme@salti.fr', "Zamin de Lomme, Rue des Fusillés, 59463 Lomme Cedex", '03.20.22.74.22'],
            ['LJ', 'Longjumeau', 'longjumeau@salti.fr', "ZI de la Vigne aux Loups, 10 avenue Arago, 91160 Longjumeau", '01.69.10.15.15'],
            ['LY', 'Lyon', 'lyon@salti.fr', "4 rue Ambroise Paré, Bâtiment D - ZI Mi-Plaine, 69800 Saint Priest", '04.72.47.69.69'],
            ['MQ', 'Marcq Agence', 'marcqagence@salti.fr', "ZI de la Pilaterie, Rue des Châteaux CS 53041, 59703 Marcq-en-Baroeul Cedex", '03.20.89.33.33'],
            ['SC', 'Marcq Services Centraux', 'marcq@salti.fr', "ZI de la Pilaterie, Rue des Châteaux CS 53041, 59703 Marcq-en-Baroeul Cedex", '03.20.92.92.92'],
            ['MS', 'Marseille', 'marseille@salti.fr', "16 boulevard de l'Europe, ZI les Estroublans, 13127 Vitrolles", '04.42.46.04.70'],
            ['MB', 'Maubeuge', 'maubeuge@salti.fr', "4 rue Robert Dubreucq, ZI de Gréveaux les Guides, 59600 Maubeuge", '03.76.46.99.00'],
            ['MZ', 'Metz', 'metz@salti.fr', "6 rue Gaston Ramon, ZI des 2 Fontaines, 57050 Metz", '03.87.30.02.02'],
            ['LB', 'Mitry Mory', 'mitrymory@salti.fr', "ZAC Mitry/Compans, 2 rue Charles Coulomb, 77290 Mitry Mory", '01.60.21.99.99'],
            ['MP', 'Montpellier', 'montpellier@salti.fr', "5 rue Jean Mermoz, 34430 Saint-Jean-de-Védas", '04.67.68.28.28'],
            ['NC', 'Nancy', 'nancy@salti.fr', "703 Rue Denis Papin, 54710 Ludres", '03.76.46.23.46'],
            ['NT', 'Nantes', 'nantes@salti.fr', "20 rue du Chêne Lassé, ZI Saint Herblain, 44800 Saint Herblain", '02.40.92.98.98'],
            ['NI', 'Nice', 'nice@salti.fr', "724 Boulevard du Mercantour, 06200 Nice", '04.92.29.16.16'],
            ['OR', 'Orléans', 'orleans@salti.fr', "25 rue Henri Dunant, 45140 Ingre", '02.38.22.48.48'],
            ['PU', 'Pau', 'pau@salti.fr', "7 rue Pierre Bourdieu, Zone Artisanale Gaston Febus, 64160 Morlaas", '05.59.60.64.64'],
            ['PO', 'Pontoise', 'pontoise@salti.fr', "1 rue Ampère, 95300 Pontoise", '01.30.30.30.01'],
            ['RS', 'Reims', 'reims@salti.fr', "13 rue Chanoine Hess, 51100 Reims", '03.26.24.55.30'],
            ['RN', 'Rennes', 'rennes@salti.fr', "6 rue de la Cerisaie, 35760 Saint Grégoire", '02.99.84.06.06'],
            ['RO', 'Rouen', 'rouen@salti.fr', "10 rue Claude Chappe, 76300 Sotteville-lès-Rouen", '02.32.83.20.20'],
            ['SA', 'Saint-Étienne', 'saintetienne@salti.fr', "8 rue du Pré Chapelon, 42270 Saint Priest en Jarez", '04.77.04.20.42'],
            ['SO', 'Saint-Omer', 'saintomer@salti.fr', "Rue Léonce Lionne, 62120 Campagne-lès-Wardrecques", '03.21.88.15.15'],
            ['SQ', 'Saint-Quentin', 'saintquentin@salti.fr', "11 avenue Abel Bardin et Charles Benoît, 02100 Rouvroy", '03.23.60.09.19'],
            ['SG', 'Strasbourg', 'strasbourg@salti.fr', "23 rue Ampère, 67720 Hoerdt", '03.88.25.55.00'],
            ['TN', 'Toulon', 'toulon@salti.fr', "543 avenue Joseph Louis Lambot, ZI Toulon Est - BP 60123 - La Garde, 83088 Toulon Cedex 9", '04.94.33.43.53'],
            ['TO', 'Toulouse', 'toulouse@salti.fr', "191 route de Paris, 31150 Fenouillet", '05.62.22.10.10'],
            ['TR', 'Tours', 'tours@salti.fr', "3 Bordebure, 37250 Sorigny", '02.47.40.40.00'],
            ['VL', 'Valence', 'valence@salti.fr', "760 rue Aristide Bergès, 26500 Bourg-lès-Valence", '04.51.62.32.62'],
            ['VA', 'Valenciennes', 'valenciennes@salti.fr', "Rue Pablo Picasso, ZI Prouvy Rouvignies n°2, 59328 Valenciennes", '03.27.21.03.03'],
        ];

        // Génère un mot de passe BTP unique par agence
        $words = self::BTP_WORDS;
        shuffle($words);
        $usedPasswords = [];
        $createdCount = 0;
        $updatedCount = 0;

        $rows = []; // pour affichage récap final

        foreach ($agencies as $i => [$code, $city, $email, $address, $phone]) {
            // Choisit un mot dans la liste (cyclic) + 2 chiffres aléatoires + @
            $word = $words[$i % count($words)];
            $digits = sprintf('%02d', random_int(10, 99));
            $plainPassword = "{$word}{$digits}@";
            // Évite les collisions improbables sur les 2 chiffres
            while (in_array($plainPassword, $usedPasswords, true)) {
                $digits = sprintf('%02d', random_int(10, 99));
                $plainPassword = "{$word}{$digits}@";
            }
            $usedPasswords[] = $plainPassword;

            $existing = User::where('email', $email)->first();
            if ($existing) {
                // On ne réécrase PAS le mot de passe existant si l'agence est déjà créée
                $existing->update([
                    'name' => "Agence {$city} ({$code})",
                    'role' => User::ROLE_AGENCY,
                    'city' => $city,
                    'address' => $address,
                    'phone' => $phone,
                ]);
                $rows[] = "  · {$email}  →  (déjà créée, mdp inchangé)";
                $updatedCount++;
            } else {
                User::create([
                    'name' => "Agence {$city} ({$code})",
                    'email' => $email,
                    'password' => Hash::make($plainPassword),
                    'role' => User::ROLE_AGENCY,
                    'city' => $city,
                    'address' => $address,
                    'phone' => $phone,
                ]);
                $rows[] = sprintf("  · %-30s →  %s", $email, $plainPassword);
                $createdCount++;
            }
        }

        $this->command->info('');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info(' Agences SALTI seedées');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info("  ✓ Créées : $createdCount");
        $this->command->info("  · Mises à jour : $updatedCount");
        $this->command->info('');
        $this->command->info(' MOTS DE PASSE INITIAUX (à transmettre 1 fois à chaque agence) :');
        foreach ($rows as $row) {
            $this->command->info($row);
        }
        $this->command->info('');
        $this->command->warn(' ⚠ NOTE BIEN ces mots de passe — ils ne seront plus affichés.');
        $this->command->warn('   Pour reset un mot de passe : /admin/agencies → Reset MDP');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    }
}

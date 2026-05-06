<?php

namespace App\Console\Commands;

use App\Models\Pdp;
use App\Models\PdpIntervenant;
use App\Models\Prestataire;
use App\Models\User;
use App\Services\PdpHtmlPdfGenerator;
use Illuminate\Console\Command;

/**
 * Crée un PDP de test entièrement rempli et génère le PDF associé.
 * Utile pour valider visuellement la calibration du mapping de coordonnées.
 *
 * Usage : php artisan pdp:test-generate
 */
class PdpTestGenerate extends Command
{
    protected $signature = 'pdp:test-generate';
    protected $description = 'Génère un PDP de test entièrement rempli pour calibration visuelle';

    public function handle(PdpHtmlPdfGenerator $generator): int
    {
        $agency = User::where('email', 'bordeaux@salti.fr')->firstOrFail();

        $prestataire = Prestataire::firstOrCreate(
            ['agency_id' => $agency->id, 'email' => 'contact@duponttp.fr'],
            [
                'raison_sociale' => 'DUPONT TP SARL',
                'responsable_nom' => 'Jean-Marc DUPONT',
                'phone' => '05.56.12.34.56',
                'address' => '12 rue des Artisans, 33000 Bordeaux',
            ]
        );

        $data = Pdp::emptyData();
        $data['eu']['agence'] = 'Bordeaux';
        $data['eu']['donneur_ordre'] = 'Marie LEFEVRE';
        $data['eu']['address'] = '5 avenue de la Sécurité, 33000 Bordeaux';
        $data['eu']['phone'] = '05.56.99.99.99';

        $data['ee']['raison_sociale'] = 'DUPONT TP SARL';
        $data['ee']['responsable_prestations'] = 'Jean-Marc DUPONT';
        $data['ee']['address'] = '12 rue des Artisans, 33000 Bordeaux';
        $data['ee']['phone'] = '05.56.12.34.56';
        $data['ee']['sous_traitance'] = 'non';

        $data['operation']['type'] = 'ponctuelle';
        $data['operation']['volume'] = 'moins_400h';
        $data['operation']['travaux_dangereux'] = false;
        $data['operation']['designation'] = 'Remplacement de la porte sectionnelle de l\'atelier 2';
        $data['operation']['lieu'] = 'Atelier 2 - Hall principal';
        $data['operation']['date_debut'] = '15/05/2026';
        $data['operation']['duree'] = '2 jours';
        $data['operation']['plages_horaires'] = '08h-17h';
        $data['operation']['nb_salaries'] = '3';

        $data['inspection']['date'] = '10/05/2026';
        $data['inspection']['participants'] = 'Marie LEFEVRE, Jean-Marc DUPONT, Lydie BERNARD';
        $data['inspection']['informations_echangees'] = 'Plan d\'accès, consignes générales, EPI requis';
        $data['inspection']['zones_visitees'] = 'Hall principal, atelier 2, vestiaires';
        $data['inspection']['observations_cssct'] = 'RAS';
        $data['inspection']['locaux']['vestiaires'] = true;
        $data['inspection']['locaux']['sanitaires'] = true;
        $data['inspection']['locaux']['refectoire'] = false;

        $data['documents_remis_ee']['plan_acces'] = true;
        $data['documents_remis_ee']['permis_feu'] = true;
        $data['documents_remis_ee']['convention_pret'] = true;
        $data['documents_remis_salti']['caces'] = true;
        $data['documents_remis_salti']['habilitations'] = true;
        $data['materiels_pretes'] = [
            ['designation' => 'Mini-pelle 2 tonnes'],
            ['designation' => 'Échafaudage roulant 6m'],
        ];
        $data['ee']['siret'] = '12345678900012';

        $data['secours']['sst_nom'] = 'Pierre MARTIN';
        $data['secours']['sst_fonction'] = 'Chef d\'atelier';
        $data['secours']['resp_ee_nom'] = 'Jean-Marc DUPONT';
        $data['secours']['resp_ee_fonction'] = 'Gérant';

        $data['epi']['chaussures'] = true;
        $data['epi']['gants'] = true;
        $data['epi']['casque'] = true;
        $data['epi']['gilet_hv'] = true;
        $data['epi']['autres'] = 'Lunettes pour la phase de découpe';

        $data['risques']['arrivee_site']['applicable'] = true;
        $data['risques']['circulation_interne']['applicable'] = true;
        $data['risques']['stationnement']['applicable'] = true;
        $data['risques']['levage_manutention']['applicable'] = true;
        $data['risques']['contamination']['applicable'] = true;

        $data['autres_risques'] = [
            [
                'situation' => 'Découpe à proximité du compresseur',
                'risque' => 'Bruit, vibrations',
                'mesure' => 'Port casque anti-bruit, arrêt compresseur si possible',
                'eu' => true,
                'ee' => true,
            ],
        ];

        $data['signature_salti_fonction'] = 'Responsable d\'agence';
        $data['signature_ee_fonction'] = 'Gérant';

        // Permis feu rempli en ligne (test du fix bug #1)
        $data['documents_remis_ee']['permis_feu'] = true;
        $data['permis_feu']['mode_remplissage'] = 'online';
        $data['permis_feu']['mode_operatoire'] = 'MOP-2026-014';
        $data['permis_feu']['operateurs_autorises'] = "Tony BERNARD\nKarim AMRANI";
        $data['permis_feu']['travaux'] = [
            'soudage' => true, 'tronconnage' => false,
            'decoupage' => true, 'meulage' => true, 'autre' => 'oxycoupage',
        ];
        $data['permis_feu']['materiels'] = [
            'poste_souder' => true, 'chalumeau' => false,
            'laser' => false, 'tronconneuse' => true, 'autre' => 'meuleuse 230mm',
        ];
        $data['permis_feu']['risques_particuliers'] = 'Présence de poussières inflammables dans la zone de découpe';
        $data['permis_feu']['zone_atex_proximite'] = true;
        $data['permis_feu']['documents_associes'] = [
            'autorisation_travail' => true, 'permis_penetrer' => false,
            'drpce' => true, 'certificat_degazage' => false,
        ];
        $data['permis_feu']['surveillance_pendant'] = 'Pierre MARTIN';
        $data['permis_feu']['surveillance_apres_de'] = '17';
        $data['permis_feu']['surveillance_apres_a'] = '19';
        $data['permis_feu']['surveillance_apres_nom'] = 'Pierre MARTIN';
        $data['permis_feu']['contact_accident_nom'] = 'Marie LEFEVRE';
        $data['permis_feu']['contact_accident_tel'] = '05.56.99.99.99';
        $data['permis_feu']['date_delivrance'] = '15/05/2026';

        // Quelques lignes Mise en sécurité remplies (pour tester le rendu PDF)
        $data['permis_feu']['mise_en_securite']['deplacement_combustibles'] = [
            'a_faire' => 'oui', 'qui' => 'Tony BERNARD', 'fait' => 'oui', 'fait_le' => '2026-05-15',
        ];
        $data['permis_feu']['mise_en_securite']['delimitation_balisage'] = [
            'a_faire' => 'oui', 'qui' => 'Karim AMRANI', 'fait' => 'non', 'fait_le' => null,
        ];
        $data['permis_feu']['mise_en_securite']['consignation'] = [
            'a_faire' => 'non', 'qui' => null, 'fait' => null, 'fait_le' => null,
        ];

        // Moyens de prévention
        $data['permis_feu']['moyens_prevention']['lutte_incendie'] = [
            'a_faire' => 'oui', 'qui' => 'Pierre MARTIN', 'fait' => 'oui', 'fait_le' => '2026-05-15',
        ];
        $data['permis_feu']['moyens_prevention']['ventilation'] = [
            'a_faire' => 'non', 'qui' => null, 'fait' => null, 'fait_le' => null,
        ];

        $pdp = Pdp::create([
            'agency_id' => $agency->id,
            'prestataire_id' => $prestataire->id,
            'mode' => Pdp::MODE_PRESENTIEL,
            'status' => Pdp::STATUS_SIGNED,
            'donneur_ordre_nom' => 'Marie LEFEVRE',
            'data' => $data,
            'signed_by_salti_at' => now(),
            'signed_by_prestataire_at' => now(),
        ]);

        // Quelques intervenants avec habilitations
        $pdp->intervenants()->createMany([
            [
                'nom_prenom' => 'Tony BERNARD',
                'date_signature' => now(),
                'habilitation' => 'CACES R489 cat 3',
                'habilitation_validity' => '2027-06-15',
            ],
            [
                'nom_prenom' => 'Karim AMRANI',
                'date_signature' => now(),
                'habilitation' => 'B2V / BR',
                'habilitation_validity' => '2027-09-30',
            ],
        ]);

        $this->info("PDP créé : id={$pdp->id} uuid={$pdp->uuid}");

        $path = $generator->generate($pdp->fresh());
        $absolutePath = storage_path('app/'.$path);

        $this->info("PDF généré : $absolutePath");
        $this->info('Taille : '.number_format(filesize($absolutePath) / 1024, 1).' KB');

        return self::SUCCESS;
    }
}

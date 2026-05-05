<?php

/*
|--------------------------------------------------------------------------
| Mapping des coordonnées du PDF officiel "Plan de prévention - PDP SALTI 2026"
|--------------------------------------------------------------------------
|
| Coordonnées en MILLIMÈTRES (origine en haut à gauche, comme FPDI/TCPDF avec PDF_UNIT="mm").
| Format A4 portrait : 210mm x 297mm.
|
| ⚠ IMPORTANT : ces coordonnées sont des estimations initiales basées sur l'analyse
| du PDF v12/2025. Une phase de CALIBRATION visuelle est nécessaire en début de
| projet : on génère le PDF avec des marqueurs (ex. carrés rouges) à chaque
| coordonnée, on superpose au template, et on ajuste jusqu'à ce que tout tombe pile.
|
| Pour calibrer, utiliser la commande :   php artisan pdp:calibrate
|
| Si SALTI met à jour le PDF officiel, il suffit de remplacer le template
| (storage/app/templates/pdp-salti-2026.pdf) et de recalibrer ce fichier.
|
*/

return [

    'template_path' => storage_path('app/templates/pdp-salti-2026.pdf'),

    'page_format' => 'A4',
    'page_orientation' => 'P',
    'unit' => 'mm',

    // Police par défaut pour le texte saisi.
    // helvetica = native TCPDF, pas de download. Pour UTF-8 complet on peut passer
    // à 'dejavusans' une fois la police téléchargée (php vendor/tecnickcom/tcpdf/tools/tcpdf_addfont.php).
    'default_font' => [
        'family' => 'helvetica',
        'style' => '',
        'size' => 10,
        'color' => [0, 0, 0],
    ],

    // Marque pour les cases cochées
    'checkbox' => [
        'mark' => 'X',
        'font_size' => 11,
    ],

    // ===========================================================================
    // PAGE 1 — Informations générales
    // ===========================================================================
    // ⚠ Coordonnées initiales en cours de calibration. Utiliser :
    //    php artisan pdp:calibrate
    //    OU le bouton "📐 Calibration" dans la nav (compte QSE)
    // pour générer un PDF avec croix rouges et ajuster.
    'page_1' => [
        // Bloc Entreprise Utilisatrice (SALTI) - colonne gauche
        'eu_agence' => ['x' => 65, 'y' => 95, 'w' => 50],          // après "Agence de ............"
        'eu_donneur_ordre' => ['x' => 80, 'y' => 102, 'w' => 50],  // après "Nom / Prénom donneur d'ordre :"
        'eu_address' => ['x' => 38, 'y' => 115, 'w' => 60],         // après "Adresse :"
        'eu_phone' => ['x' => 40, 'y' => 122, 'w' => 60],           // après "Téléphone :"

        // Bloc Entreprise Extérieure - colonne droite
        'ee_raison_sociale' => ['x' => 145, 'y' => 95, 'w' => 60],
        'ee_responsable' => ['x' => 145, 'y' => 108, 'w' => 60],
        'ee_address' => ['x' => 145, 'y' => 115, 'w' => 60],
        'ee_phone' => ['x' => 145, 'y' => 122, 'w' => 60],
        'ee_sous_traitance_oui' => ['x' => 162, 'y' => 130, 'type' => 'checkbox'],
        'ee_sous_traitance_non' => ['x' => 180, 'y' => 130, 'type' => 'checkbox'],

        // Nature de l'opération
        'op_ponctuelle' => ['x' => 70, 'y' => 192, 'type' => 'checkbox'],
        'op_annuelle' => ['x' => 70, 'y' => 198, 'type' => 'checkbox'],
        'op_moins_400h' => ['x' => 130, 'y' => 192, 'type' => 'checkbox'],
        'op_plus_400h' => ['x' => 130, 'y' => 198, 'type' => 'checkbox'],
        'op_travaux_dangereux' => ['x' => 70, 'y' => 204, 'type' => 'checkbox'],
        'op_designation' => ['x' => 70, 'y' => 210, 'w' => 130],
        'op_lieu' => ['x' => 60, 'y' => 218, 'w' => 140],
        'op_date_debut' => ['x' => 60, 'y' => 226, 'w' => 50],
        'op_duree' => ['x' => 145, 'y' => 226, 'w' => 50],
        'op_plages_horaires' => ['x' => 35, 'y' => 233, 'w' => 60],
        'op_nb_salaries' => ['x' => 145, 'y' => 233, 'w' => 30],

        // Inspection commune
        'insp_date' => ['x' => 50, 'y' => 250, 'w' => 100],
        'insp_participants' => ['x' => 60, 'y' => 256, 'w' => 140],
        'insp_informations' => ['x' => 95, 'y' => 262, 'w' => 105],
        'insp_zones' => ['x' => 38, 'y' => 268, 'w' => 162],
        'insp_observations' => ['x' => 65, 'y' => 274, 'w' => 135],
        'insp_vestiaires' => ['x' => 75, 'y' => 286, 'type' => 'checkbox'],
        'insp_sanitaires' => ['x' => 110, 'y' => 286, 'type' => 'checkbox'],
        'insp_refectoire' => ['x' => 145, 'y' => 286, 'type' => 'checkbox'],
    ],

    // ===========================================================================
    // PAGE 2 — Documents et organisation des secours
    // ===========================================================================
    'page_2' => [
        // Documents remis au sous-traitant (par SALTI)
        'doc_plan_acces' => ['x' => 25, 'y' => 53, 'type' => 'checkbox'],
        'doc_permis_feu' => ['x' => 25, 'y' => 65, 'type' => 'checkbox'],
        'doc_convention_pret' => ['x' => 25, 'y' => 71, 'type' => 'checkbox'],

        // Documents remis à SALTI (par EE)
        'doc_autorisation_conduite' => ['x' => 115, 'y' => 53, 'type' => 'checkbox'],
        'doc_caces' => ['x' => 115, 'y' => 59, 'type' => 'checkbox'],
        'doc_habilitations' => ['x' => 115, 'y' => 65, 'type' => 'checkbox'],

        // Organisation des secours
        'sst_nom' => ['x' => 30, 'y' => 117, 'w' => 80],
        'sst_fonction' => ['x' => 30, 'y' => 126, 'w' => 80],
        'resp_ee_nom' => ['x' => 30, 'y' => 144, 'w' => 80],
        'resp_ee_fonction' => ['x' => 30, 'y' => 153, 'w' => 80],
    ],

    // ===========================================================================
    // PAGE 3 — Risques (1ère partie) - EPI + premières lignes du tableau
    // ===========================================================================
    'page_3' => [
        // EPI obligatoires (cases cochées sur la ligne "Obligatoire")
        'epi_chaussures' => ['x' => 50, 'y' => 79, 'type' => 'checkbox'],
        'epi_gants' => ['x' => 65, 'y' => 79, 'type' => 'checkbox'],
        'epi_casque' => ['x' => 80, 'y' => 79, 'type' => 'checkbox'],
        'epi_lunettes' => ['x' => 95, 'y' => 79, 'type' => 'checkbox'],
        'epi_masque' => ['x' => 110, 'y' => 79, 'type' => 'checkbox'],
        'epi_auditives' => ['x' => 125, 'y' => 79, 'type' => 'checkbox'],
        'epi_gilet_hv' => ['x' => 140, 'y' => 79, 'type' => 'checkbox'],
        'epi_harnais' => ['x' => 165, 'y' => 79, 'type' => 'checkbox'],
        'epi_autres' => ['x' => 35, 'y' => 96, 'w' => 165],

        // Tableau des risques (cases "applicable" + responsabilités EU/EE)
        // Y est ajusté ligne par ligne. X applicable=63, X eu=176, X ee=190
        'risk_arrivee_site_applicable' => ['x' => 63, 'y' => 145, 'type' => 'checkbox'],
        'risk_circulation_interne_applicable' => ['x' => 63, 'y' => 175, 'type' => 'checkbox'],
        'risk_stationnement_applicable' => ['x' => 63, 'y' => 215, 'type' => 'checkbox'],
        'risk_sols_souilles_applicable' => ['x' => 63, 'y' => 250, 'type' => 'checkbox'],
    ],

    // ===========================================================================
    // PAGE 4 — Risques (suite)
    // ===========================================================================
    'page_4' => [
        'risk_travail_hauteur_applicable' => ['x' => 63, 'y' => 50, 'type' => 'checkbox'],
        'risk_levage_manutention_applicable' => ['x' => 63, 'y' => 90, 'type' => 'checkbox'],
        'risk_soudure_decoupe_applicable' => ['x' => 63, 'y' => 135, 'type' => 'checkbox'],
        'risk_dechets_applicable' => ['x' => 63, 'y' => 195, 'type' => 'checkbox'],
        'risk_electrique_applicable' => ['x' => 63, 'y' => 225, 'type' => 'checkbox'],
        'risk_produits_chimiques_applicable' => ['x' => 63, 'y' => 250, 'type' => 'checkbox'],
        'risk_flexibles_engins_applicable' => ['x' => 63, 'y' => 275, 'type' => 'checkbox'],
    ],

    // ===========================================================================
    // PAGE 5 — Risques (fin) + autres risques + habilitations
    // ===========================================================================
    'page_5' => [
        'risk_multi_interventions_applicable' => ['x' => 63, 'y' => 50, 'type' => 'checkbox'],
        'risk_contamination_applicable' => ['x' => 63, 'y' => 75, 'type' => 'checkbox'],

        // Tableau "Autres risques" - 5 lignes possibles
        // Ligne 1
        'autre_risque_1_situation' => ['x' => 13, 'y' => 122, 'w' => 50],
        'autre_risque_1_risque' => ['x' => 65, 'y' => 122, 'w' => 50],
        'autre_risque_1_mesure' => ['x' => 117, 'y' => 122, 'w' => 50],
        'autre_risque_1_eu' => ['x' => 177, 'y' => 122, 'type' => 'checkbox'],
        'autre_risque_1_ee' => ['x' => 192, 'y' => 122, 'type' => 'checkbox'],
        // Ligne 2
        'autre_risque_2_situation' => ['x' => 13, 'y' => 132, 'w' => 50],
        'autre_risque_2_risque' => ['x' => 65, 'y' => 132, 'w' => 50],
        'autre_risque_2_mesure' => ['x' => 117, 'y' => 132, 'w' => 50],
        'autre_risque_2_eu' => ['x' => 177, 'y' => 132, 'type' => 'checkbox'],
        'autre_risque_2_ee' => ['x' => 192, 'y' => 132, 'type' => 'checkbox'],

        // Habilitations / CACES (3 lignes)
        'hab_1_salarie' => ['x' => 15, 'y' => 213, 'w' => 60],
        'hab_1_habilitation' => ['x' => 80, 'y' => 213, 'w' => 60],
        'hab_1_validity' => ['x' => 145, 'y' => 213, 'w' => 50],
        'hab_2_salarie' => ['x' => 15, 'y' => 230, 'w' => 60],
        'hab_2_habilitation' => ['x' => 80, 'y' => 230, 'w' => 60],
        'hab_2_validity' => ['x' => 145, 'y' => 230, 'w' => 50],
        'hab_3_salarie' => ['x' => 15, 'y' => 247, 'w' => 60],
        'hab_3_habilitation' => ['x' => 80, 'y' => 247, 'w' => 60],
        'hab_3_validity' => ['x' => 145, 'y' => 247, 'w' => 50],
    ],

    // ===========================================================================
    // PAGE 6 — Attestation et signatures
    // ===========================================================================
    'page_6' => [
        // Attestation de prise de connaissance (4 lignes)
        'intervenant_1_nom' => ['x' => 15, 'y' => 80, 'w' => 90],
        'intervenant_1_date' => ['x' => 110, 'y' => 80, 'w' => 30],
        'intervenant_1_signature' => ['x' => 145, 'y' => 75, 'w' => 50, 'h' => 13, 'type' => 'image'],
        'intervenant_2_nom' => ['x' => 15, 'y' => 95, 'w' => 90],
        'intervenant_2_date' => ['x' => 110, 'y' => 95, 'w' => 30],
        'intervenant_2_signature' => ['x' => 145, 'y' => 90, 'w' => 50, 'h' => 13, 'type' => 'image'],
        'intervenant_3_nom' => ['x' => 15, 'y' => 110, 'w' => 90],
        'intervenant_3_date' => ['x' => 110, 'y' => 110, 'w' => 30],
        'intervenant_3_signature' => ['x' => 145, 'y' => 105, 'w' => 50, 'h' => 13, 'type' => 'image'],
        'intervenant_4_nom' => ['x' => 15, 'y' => 125, 'w' => 90],
        'intervenant_4_date' => ['x' => 110, 'y' => 125, 'w' => 30],
        'intervenant_4_signature' => ['x' => 145, 'y' => 120, 'w' => 50, 'h' => 13, 'type' => 'image'],

        // Signatures finales des représentants — boîtes ENTREPRISE UTILISATRICE / EXTERIEURE
        // Boîtes en bas de page : approximativement y=215 (titre) à y=275 (bas)
        // Texte saisi va dans la moitié haute, signature image dans la moitié basse
        'sign_salti_nom' => ['x' => 30, 'y' => 222, 'w' => 70],         // après "Prénom NOM :"
        'sign_salti_fonction' => ['x' => 25, 'y' => 232, 'w' => 70],    // après "Fonction :"
        'sign_salti_date' => ['x' => 50, 'y' => 242, 'w' => 50],        // après "Date de signature :"
        'sign_salti_image' => ['x' => 25, 'y' => 248, 'w' => 70, 'h' => 22, 'type' => 'image'],

        'sign_ee_nom' => ['x' => 130, 'y' => 222, 'w' => 70],
        'sign_ee_fonction' => ['x' => 125, 'y' => 232, 'w' => 70],
        'sign_ee_date' => ['x' => 150, 'y' => 242, 'w' => 50],
        'sign_ee_image' => ['x' => 125, 'y' => 248, 'w' => 70, 'h' => 22, 'type' => 'image'],
    ],
];

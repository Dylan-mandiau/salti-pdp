{{--
    Plan de Prévention SALTI 2026 — version reconstruite en HTML pour mPDF.
    Le rendu reproduit fidèlement le modèle officiel (PR0103, version 12/25)
    avec les mêmes images extraites du PDF d'origine.

    Vars dispo : $pdp, $data, $intervenants, $agency, $assets (chemin absolu)
--}}
@php
    $cb = fn($v) => !empty($v) ? '☒' : '☐';
    // Pas de e() ici : Blade {{ }} escape déjà — sinon double-encoding (d&#039;agence)
    $val = fn($v) => $v !== null && $v !== '' ? $v : '';
    /**
     * Affiche une date au format jj/mm/aaaa peu importe le format d'entrée.
     */
    $date = function ($v) {
        if (empty($v)) return '';
        try { return \Carbon\Carbon::parse($v)->format('d/m/Y'); }
        catch (\Throwable $e) { return $v; }
    };
    /**
     * Construit la durée affichable depuis duree_value + duree_unit.
     * Fallback : valeur "duree" en string brute si présente (compat).
     */
    $duree = function () use ($data) {
        $v = $data['operation']['duree_value'] ?? null;
        $u = $data['operation']['duree_unit'] ?? null;
        if ($v !== null && $v !== '' && !empty($u)) {
            return trim($v.' '.$u);
        }
        return $data['operation']['duree'] ?? '';
    };
    /**
     * Plage horaire au format "HH:MM - HH:MM" depuis plage_debut + plage_fin.
     */
    $plageHoraire = function () use ($data) {
        $debut = $data['operation']['plage_debut'] ?? null;
        $fin = $data['operation']['plage_fin'] ?? null;
        if ($debut && $fin) return $debut.' - '.$fin;
        if ($debut) return $debut;
        return $data['operation']['plages_horaires'] ?? '';
    };
    $risques = $data['risques'] ?? [];
    $epi = $data['epi'] ?? [];
    $autresRisques = collect($data['autres_risques'] ?? [])->take(5);
    while ($autresRisques->count() < 5) $autresRisques->push([]);
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
    body { font-family: arial, sans-serif; font-size: 9pt; color: #000; line-height: 1.25; }

    h1, h2, h3 { margin: 0; padding: 0; }
    p { margin: 0 0 4px 0; }
    table { border-collapse: collapse; width: 100%; }
    td, th { padding: 4px 6px; vertical-align: top; }

    /* En-tête */
    .header { width: 100%; margin-bottom: 6px; }
    .header-logo { width: 110px; }
    .header-bandeau { width: 380px; }
    .ref-block {
        background: #efefef;
        text-align: center;
        font-size: 8.5pt;
        padding: 4px 8px;
        margin-bottom: 8px;
        font-style: italic;
    }
    .ref-block strong { font-weight: bold; }

    /* Titres jaunes */
    .title-yellow {
        background: #FFC000;
        font-weight: bold;
        text-align: center;
        font-size: 10pt;
        padding: 5px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    /* Bordures */
    .bordered { border: 1px solid #000; }
    .bordered td, .bordered th { border: 1px solid #000; }

    /* Tableau EU/EE page 1 */
    .eu-ee th {
        background: #FFC000;
        text-align: center;
        font-size: 9pt;
        font-variant: small-caps;
    }
    .eu-ee td.cell { padding: 6px 8px; min-height: 60px; }
    .interlocuteurs td {
        background: #fafafa;
        font-size: 8.5pt;
        padding: 6px;
    }

    /* Champs renseignés */
    .field-label { color: #000; }
    .field-value {
        font-weight: bold;
        color: #000;
    }

    /* EPI table — unités mm pour rendu mPDF stable */
    .epi-table td {
        text-align: center;
        border: 1px solid #000;
        padding: 2mm 1mm;
        vertical-align: middle;
    }
    .epi-table .icon-row td { height: 18mm; }
    .epi-table .icon-row img {
        height: 16mm;
        width: auto;
    }
    .epi-table .obligatoire-row td {
        height: 8mm;
        padding: 2mm;
        font-size: 11pt;
        font-weight: bold;
    }
    .epi-table .obligatoire-label {
        background: #fafafa;
        text-align: left;
        padding-left: 4mm;
        font-size: 10pt;
    }

    /* Tableau des risques */
    .risques th { background: #FFC000; font-size: 8.5pt; }
    .risques td { font-size: 8pt; }
    .risques .col-applicable { width: 8%; text-align: center; font-size: 12pt; font-weight: bold; }
    .risques .col-resp { width: 6%; text-align: center; font-size: 11pt; font-weight: bold; }

    /* Signatures */
    .signature-canvas {
        width: 100%;
        height: 60px;
        text-align: center;
    }
    .signature-canvas img { max-width: 80%; max-height: 60px; }
</style>
</head>
<body>

{{-- ════════════════════════════════════════════════════════════════════════
     PAGE 1 — Informations générales
     ════════════════════════════════════════════════════════════════════════ --}}
<div>

    <table class="header"><tr>
        <td style="width:140px;"><img src="{{ $assets }}logo-salti.png" class="header-logo"></td>
        <td style="text-align:center;"><img src="{{ $assets }}header-bandeau.png" class="header-bandeau"></td>
    </tr></table>

    <div class="ref-block">
        <strong>Référence :</strong> PR0103 |
        <strong>Date d'application :</strong> 23/07/2012 |
        <strong>Mise à jour :</strong> version 12/25<br>
        <strong>Auteur :</strong> service QSE |
        <strong>Personnes concernées :</strong> personnel SALTI / habilité
    </div>

    {{-- Tableau Entreprise Utilisatrice / Extérieure --}}
    <table class="bordered eu-ee">
        <tr>
            <th style="width:50%">Entreprise utilisatrice (SALTI)</th>
            <th style="width:50%">Entreprise extérieure</th>
        </tr>
        <tr>
            <td class="cell">
                <p><strong>SALTI</strong></p>
                <p><span class="field-label">Agence de </span><span class="field-value">{{ $val($data['eu']['agence'] ?? null) }}</span></p>
                <p><span class="field-label">Nom / Prénom donneur d'ordre : </span><span class="field-value">{{ $val($data['eu']['donneur_ordre'] ?? $pdp->donneur_ordre_nom) }}</span></p>
                <p style="margin-top:8px"><span class="field-label">Adresse : </span><span class="field-value">{{ $val($data['eu']['address'] ?? null) }}</span></p>
                <p><span class="field-label">Téléphone : </span><span class="field-value">{{ $val($data['eu']['phone'] ?? null) }}</span></p>
            </td>
            <td class="cell">
                <p><span class="field-label">Raison sociale : </span><span class="field-value">{{ $val($data['ee']['raison_sociale'] ?? null) }}</span></p>
                <p><span class="field-label">Responsable des prestations : </span><span class="field-value">{{ $val($data['ee']['responsable_prestations'] ?? null) }}</span></p>
                <p><span class="field-label">Adresse : </span><span class="field-value">{{ $val($data['ee']['address'] ?? null) }}</span></p>
                <p><span class="field-label">Téléphone : </span><span class="field-value">{{ $val($data['ee']['phone'] ?? null) }}</span></p>
                <p style="margin-top:6px">
                    Travaux sous traités ?
                    {{ $cb(($data['ee']['sous_traitance'] ?? null) === 'oui') }} OUI &nbsp;
                    {{ $cb(($data['ee']['sous_traitance'] ?? null) === 'non') }} NON
                </p>
                <p style="font-size:7.5pt;font-style:italic;color:#333">
                    Si oui : faire signer pdp avec les autres sous traitants
                </p>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="text-align:center; padding:3px; background:#FFC000; font-weight:bold; font-variant:small-caps;">
                Interlocuteurs sécurité :
            </td>
        </tr>
        <tr>
            <td colspan="2" class="interlocuteurs">
                <table style="width:100%"><tr>
                    <td style="width:50%; text-align:center;">
                        @foreach($interlocutors as $contact)
                            @if($contact->is_main)<strong>{{ $contact->name }}</strong>@else{{ $contact->name }}@endif
                            @if($contact->role) - {{ $contact->role }}@endif
                            @if($contact->phone) — {{ $contact->phone }}@endif
                            @if($contact->email)<br><span style="color:#0070c0;text-decoration:underline">{{ $contact->email }}</span>@endif
                            <br>
                        @endforeach
                    </td>
                    <td style="width:50%"></td>
                </tr></table>
            </td>
        </tr>
    </table>

    {{-- Nature de l'opération --}}
    <table class="bordered" style="margin-top:8px">
        <tr><th colspan="2" class="title-yellow">Nature de l'opération</th></tr>
        <tr>
            <td style="width:30%; vertical-align:middle;">Opération :</td>
            <td>
                <table style="width:100%"><tr>
                    <td style="width:33%">{{ $cb(($data['operation']['type'] ?? null) === 'ponctuelle') }} Ponctuelle</td>
                    <td style="width:33%">{{ $cb(($data['operation']['volume'] ?? null) === 'moins_400h') }} Moins de 400 heures</td>
                    <td></td>
                </tr><tr>
                    <td>{{ $cb(($data['operation']['type'] ?? null) === 'annuelle') }} Annuelle</td>
                    <td>{{ $cb(($data['operation']['volume'] ?? null) === 'plus_400h') }} Plus de 400 heures (sur 12 mois)</td>
                    <td></td>
                </tr><tr>
                    <td colspan="3">{{ $cb($data['operation']['travaux_dangereux'] ?? false) }} Travaux dangereux (définis par l'arrêté du 19/03/93)</td>
                </tr></table>
            </td>
        </tr>
        <tr><td>Désignation de l'opération :</td><td><span class="field-value">{{ $val($data['operation']['designation'] ?? null) }}</span></td></tr>
        <tr><td>Lieu de l'opération (zone…) :</td><td><span class="field-value">{{ $val($data['operation']['lieu'] ?? null) }}</span></td></tr>
        <tr>
            <td>Date de début de l'intervention :</td>
            <td>
                <span class="field-value">{{ $date($data['operation']['date_debut'] ?? null) }}</span>
                &nbsp;&nbsp;&nbsp;&nbsp;
                Durée prévisible : <span class="field-value">{{ $duree() }}</span>
            </td>
        </tr>
        <tr>
            <td>Plages horaires :</td>
            <td>
                <span class="field-value">{{ $plageHoraire() }}</span>
                &nbsp;&nbsp;&nbsp;&nbsp;
                Nombre de salariés affectés : <span class="field-value">{{ $val($data['operation']['nb_salaries'] ?? null) }}</span>
            </td>
        </tr>
    </table>

    {{-- Inspection commune --}}
    <table class="bordered" style="margin-top:8px">
        <tr><th class="title-yellow">Inspection commune avant le début des travaux (À compléter obligatoirement)</th></tr>
        <tr><td>Date de l'inspection : <span class="field-value">{{ $date($data['inspection']['date'] ?? null) }}</span></td></tr>
        <tr><td>Participants à l'inspection : <span class="field-value">{{ $val($data['inspection']['participants'] ?? null) }}</span></td></tr>
        <tr><td>Informations échangées et/ou les documents communiqués : <span class="field-value">{{ $val($data['inspection']['informations_echangees'] ?? null) }}</span></td></tr>
        <tr><td>Zones visitées : <span class="field-value">{{ $val($data['inspection']['zones_visitees'] ?? null) }}</span></td></tr>
        <tr><td>Observations émises par le CSSCT : <span class="field-value">{{ $val($data['inspection']['observations_cssct'] ?? null) }}</span></td></tr>
        <tr><td>
            Locaux sociaux mis à disposition du personnel des entreprises extérieurs :<br>
            <span style="margin-left:30px">
                {{ $cb($data['inspection']['locaux']['vestiaires'] ?? false) }} Vestiaires &nbsp;&nbsp;
                {{ $cb($data['inspection']['locaux']['sanitaires'] ?? false) }} Sanitaires &nbsp;&nbsp;
                {{ $cb($data['inspection']['locaux']['refectoire'] ?? false) }} Réfectoire
            </span>
        </td></tr>
    </table>

</div>

<pagebreak />

{{-- ════════════════════════════════════════════════════════════════════════
     PAGE 2 — Documents échangés + Secours + Image consigne accident
     ════════════════════════════════════════════════════════════════════════ --}}
<div>

    <img src="{{ $assets }}logo-salti.png" style="width:80px;margin-bottom:6px">

    <table class="bordered" style="margin-top:6px">
        <tr>
            <th class="title-yellow" style="width:50%">DOCUMENTS REMIS au sous traitant</th>
            <th class="title-yellow" style="width:50%">DOCUMENTS REMIS à SALTI</th>
        </tr>
        <tr>
            <td>
                <p>{{ $cb($data['documents_remis_ee']['plan_acces'] ?? false) }} Plan (accès, circulation, zone d'attente) avec modalités d'accès et de stationnement</p>
                <p>{{ $cb($data['documents_remis_ee']['permis_feu'] ?? false) }} Permis feu</p>
                <p>{{ $cb($data['documents_remis_ee']['convention_pret'] ?? false) }} Convention de prêt de matériel</p>
            </td>
            <td>
                <p>{{ $cb($data['documents_remis_salti']['autorisation_conduite'] ?? false) }} Autorisation de conduite</p>
                <p>{{ $cb($data['documents_remis_salti']['caces'] ?? false) }} CACES</p>
                <p>{{ $cb($data['documents_remis_salti']['habilitations'] ?? false) }} Habilitations</p>
            </td>
        </tr>
    </table>

    <table class="bordered" style="margin-top:8px">
        <tr><th class="title-yellow">Organisation des secours</th></tr>
        <tr><td style="text-align:center; font-size:9pt;">
            Au moins une personne est formée SST sur chacun de nos sites<br>
            Une boîte à pharmacie est présente sur chaque site
        </td></tr>
        <tr><td style="text-align:center; font-weight:bold; font-size:11pt;">
            Secours : <span style="color:#c00">15</span> / <span style="color:#c00">18</span> / <span style="color:#c00">112</span><br>
            <span style="font-size:9pt">Personnes à prévenir</span>
        </td></tr>
        <tr><td>
            <strong style="display:block; text-align:center; margin-bottom:6px">Personne formée SST à l'agence</strong>
            <span class="field-label">Nom : </span><span class="field-value">{{ $val($data['secours']['sst_nom'] ?? null) }}</span><br>
            <span class="field-label">Fonction : </span><span class="field-value">{{ $val($data['secours']['sst_fonction'] ?? null) }}</span>
        </td></tr>
        <tr><td>
            <strong style="display:block; text-align:center; margin-bottom:6px">Responsable entreprise extérieure</strong>
            <span class="field-label">Nom : </span><span class="field-value">{{ $val($data['secours']['resp_ee_nom'] ?? null) }}</span><br>
            <span class="field-label">Fonction : </span><span class="field-value">{{ $val($data['secours']['resp_ee_fonction'] ?? null) }}</span>
        </td></tr>
    </table>

    <p style="margin-top:10px"><strong>Consignes à respecter en cas d'accident :</strong></p>
    <div style="text-align:center; margin-top:6px">
        <img src="{{ $assets }}consigne-accident.png" style="width:90%; max-width:520px;">
    </div>

</div>

<pagebreak />

{{-- ════════════════════════════════════════════════════════════════════════
     PAGE 3 — EPI + premières lignes du tableau des risques
     ════════════════════════════════════════════════════════════════════════ --}}
<div>

    <img src="{{ $assets }}logo-salti.png" style="width:80px;margin-bottom:6px">

    <div class="title-yellow" style="margin-top:4px">
        Risques liés à l'interférence entre les activités, les installations, les matériels<br>
        <span style="font-weight:normal; font-style:italic; font-size:9pt">(cocher les cases concernées)</span>
    </div>

    <p style="margin-top:6px; font-size:8.5pt; font-style:italic">
        &nbsp;&nbsp;&nbsp;Le responsable de l'entreprise extérieure ou son préposé a l'obligation, avant le début des travaux,
        de reprendre et d'expliquer à ses salariés ainsi qu'aux responsables des entreprises sous-traitantes qu'il emploie,
        les risques, les mesures de prévention, les consignes et procédures de travail, ainsi que les documents pédagogiques
        qui lui ont été remis par l'entreprise utilisatrice à la signature du plan de prévention.
    </p>

    {{-- Bandeau EPI --}}
    @php $iconH = 60; /* hauteur des pictos EPI en px (rendu mPDF) */ @endphp
    <table class="bordered epi-table" style="margin-top:8px; table-layout:fixed">
        <colgroup>
            <col style="width:14%"> {{-- Flèche + label Obligatoire --}}
            <col style="width:9.5%"><col style="width:9.5%"><col style="width:9.5%">
            <col style="width:9.5%"><col style="width:9.5%"><col style="width:9.5%">
            <col style="width:9.5%"><col style="width:9.5%"><col style="width:9.5%">
        </colgroup>
        <tr class="icon-row">
            <td><img src="{{ $assets }}arrow-obligatoire.png" height="{{ $iconH }}"></td>
            <td><img src="{{ $assets }}epi-ouvrier.png" height="{{ $iconH }}"></td>
            <td><img src="{{ $assets }}epi-chaussures.png" height="{{ $iconH }}"></td>
            <td><img src="{{ $assets }}epi-gants.png" height="{{ $iconH }}"></td>
            <td><img src="{{ $assets }}epi-casque.png" height="{{ $iconH }}"></td>
            <td><img src="{{ $assets }}epi-lunettes.png" height="{{ $iconH }}"></td>
            <td><img src="{{ $assets }}epi-masque.png" height="{{ $iconH }}"></td>
            <td><img src="{{ $assets }}epi-auditives.png" height="{{ $iconH }}"></td>
            <td><img src="{{ $assets }}epi-gilet-hv.png" height="{{ $iconH }}"></td>
            <td><img src="{{ $assets }}epi-harnais.jpg" height="{{ $iconH }}"></td>
        </tr>
        <tr class="obligatoire-row">
            <td class="obligatoire-label">Obligatoire</td>
            <td></td>
            <td>{!! ($epi['chaussures'] ?? false) ? '<strong>X</strong>' : '' !!}</td>
            <td>{!! ($epi['gants'] ?? false) ? '<strong>X</strong>' : '' !!}</td>
            <td>{!! ($epi['casque'] ?? false) ? '<strong>X</strong>' : '' !!}</td>
            <td>{!! ($epi['lunettes'] ?? false) ? '<strong>X</strong>' : '' !!}</td>
            <td>{!! ($epi['masque'] ?? false) ? '<strong>X</strong>' : '' !!}</td>
            <td>{!! ($epi['auditives'] ?? false) ? '<strong>X</strong>' : '' !!}</td>
            <td>{!! ($epi['gilet_hv'] ?? false) ? '<strong>X</strong>' : '' !!}</td>
            <td>{!! ($epi['harnais'] ?? false) ? '<strong>X</strong>' : '' !!}</td>
        </tr>
    </table>

    <p style="margin-top:6px"><strong>Autre EPI :</strong> <span class="field-value">{{ $val($epi['autres'] ?? null) }}</span></p>

    {{-- Tableau des risques (page 3 - 4 premières lignes) --}}
    <table class="bordered risques" style="margin-top:6px">
        <tr>
            <th style="width:25%">Situation de travail concernée</th>
            <th class="col-applicable"></th>
            <th style="width:30%">Risques de l'interférence</th>
            <th style="width:30%">Mesures de prévention</th>
            <th class="col-resp">EU</th>
            <th class="col-resp">EE</th>
        </tr>

        @include('pdf.partials.risque', [
            'situation' => 'Arrivée sur le site',
            'applicable' => $risques['arrivee_site']['applicable'] ?? false,
            'risque' => '',
            'mesure' => "Se garer en marche arrière\nSe présenter à l'accueil\nBalisage du secteur d'intervention\nRaccordement aux réseaux de fluides et règles de consignation.\nStockage des matériels, produits ou déchets\nPrécautions particulières liées à la zone d'intervention\nRespect des règles de circulation et des limitations de vitesse sur site à 20 km/h",
            'eu' => $risques['arrivee_site']['eu'] ?? false,
            'ee' => $risques['arrivee_site']['ee'] ?? true,
        ])

        @include('pdf.partials.risque', [
            'situation' => "Circulation interne avec un véhicule, un engin ou à pied",
            'applicable' => $risques['circulation_interne']['applicable'] ?? false,
            'risque' => "Collision avec d'autres véhicules ou engins.\nRenverser un piéton ou se faire renverser par un véhicule.",
            'mesure' => "Respecter les règles d'accès du site, de circulation, du stationnement, de la vitesse et des piétons.",
            'eu' => $risques['circulation_interne']['eu'] ?? true,
            'ee' => $risques['circulation_interne']['ee'] ?? true,
        ])

        @include('pdf.partials.risque', [
            'situation' => "Stationnement avec un véhicule ou un engin, entreposage de matériel ou de matériaux sur les voies de circulation interne de l'entreprise utilisatrice",
            'applicable' => $risques['stationnement']['applicable'] ?? false,
            'risque' => "Collision avec d'autres véhicules ou engins qui circulent dans l'entreprise utilisatrice. Heurt de personnes par des véhicules ou des engins qui sont obligés de contourner.\nEntrave à l'intervention des secours.",
            'mesure' => "Respect des règles de stationnement et d'entreposage et tout particulièrement devant les moyens d'extinction d'incendie, une issue de secours, un transformateur",
            'eu' => $risques['stationnement']['eu'] ?? false,
            'ee' => $risques['stationnement']['ee'] ?? true,
        ])

        @include('pdf.partials.risque', [
            'situation' => "Sols souillés par des produits, des liquides ; encombrés par des outils, des pièces",
            'applicable' => $risques['sols_souilles']['applicable'] ?? false,
            'risque' => "Chute, glissade de personnes circulant dans la zone de travail",
            'mesure' => "Nettoyage des sols au fur et à mesure des salissures et rangement de la zone de travail.",
            'eu' => $risques['sols_souilles']['eu'] ?? false,
            'ee' => $risques['sols_souilles']['ee'] ?? true,
        ])
    </table>

    <p style="font-size:7pt; margin-top:4px">
        <sup>1</sup> EU = Entreprise Utilisatrice SALTI &nbsp;&nbsp;
        <sup>2</sup> EE = Entreprise Extérieure
    </p>

</div>

<pagebreak />

{{-- ════════════════════════════════════════════════════════════════════════
     PAGE 4 — Suite du tableau des risques
     ════════════════════════════════════════════════════════════════════════ --}}
<div>

    <img src="{{ $assets }}logo-salti.png" style="width:80px;margin-bottom:6px">

    <table class="bordered risques" style="margin-top:6px">
        <tr>
            <th style="width:25%">Situation de travail concernée</th>
            <th class="col-applicable"></th>
            <th style="width:30%">Risques de l'interférence</th>
            <th style="width:30%">Mesures de prévention</th>
            <th class="col-resp">EU</th>
            <th class="col-resp">EE</th>
        </tr>

        @include('pdf.partials.risque', [
            'situation' => "Travail en hauteur — Utilisation d'une nacelle",
            'applicable' => $risques['travail_hauteur']['applicable'] ?? false,
            'risque' => "Chute d'objets\nChute de personnes",
            'mesure' => "Balisage de la zone\nUtilisation d'une échelle possible, uniquement comme moyen d'accès et non comme poste de travail.\nAttestations de formations du travail en hauteur et du port du harnais, autorisation de conduite délivrée par l'employeur du conducteur.",
            'eu' => $risques['travail_hauteur']['eu'] ?? false,
            'ee' => $risques['travail_hauteur']['ee'] ?? true,
        ])

        @include('pdf.partials.risque', [
            'situation' => "Levage et manutention de parties ou de la totalité d'un équipement de travail.",
            'applicable' => $risques['levage_manutention']['applicable'] ?? false,
            'risque' => "Balancement ou décrochement de la charge et écrasement de personnes travaillant ou circulant dans la zone de travail.\nRupture ou mauvais état des accessoires de levage tels qu'élingues, crochets et chute de la charge sur des personnes travaillant et circulant dans la zone de travail",
            'mesure' => "Balisage de la zone\nAutorisation de conduite délivrée par l'employeur du conducteur",
            'eu' => $risques['levage_manutention']['eu'] ?? false,
            'ee' => $risques['levage_manutention']['ee'] ?? true,
        ])

        @include('pdf.partials.risque', [
            'situation' => "Soudure / Découpe de matériaux — Utilisation de matériels portatifs : poste de soudure, meuleuse à disque, tronçonneuse..",
            'applicable' => $risques['soudure_decoupe']['applicable'] ?? false,
            'risque' => "Incendie, coupure, projection de particules dans les yeux\nBrûlures de personnes circulant dans la zone de travail par projection de matières en fusion\nIrritation oculaire de personnes circulant dans la zone de travail par rayonnement dans le cas de la soudure à l'arc.\nRupture et projection de morceaux du disque de tronçonnage sur des personnes circulant ou travaillant dans la zone",
            'mesure' => "Interdiction de souder/meuler ou tronçonner à côté des zones potentiellement ATEX (charges de batteries, cuves à fioul, stockage d'essence)\nMatériel utilisé en bon état. Port des EPI obligatoires. Balisage de la zone",
            'eu' => $risques['soudure_decoupe']['eu'] ?? false,
            'ee' => $risques['soudure_decoupe']['ee'] ?? true,
        ])

        @include('pdf.partials.risque', [
            'situation' => "Déchets produits par l'activité",
            'applicable' => $risques['dechets']['applicable'] ?? false,
            'risque' => "Pollution de l'environnement\nSurcoût pour l'entreprise utilisatrice du à un non respect du tri des déchets",
            'mesure' => "Respect du tri des déchets\nInterdiction de déverser quelque matière que ce soit dans le réseau d'eaux pluviales ou le réseau d'eaux usées de l'entreprise",
            'eu' => $risques['dechets']['eu'] ?? false,
            'ee' => $risques['dechets']['ee'] ?? true,
        ])

        @include('pdf.partials.risque', [
            'situation' => "Intervention sur des installations électriques : coffret, câblage. Travaux neufs et dépannages",
            'applicable' => $risques['electrique']['applicable'] ?? false,
            'risque' => "Électrocution, incendie, détérioration matériel",
            'mesure' => "Les opérateurs n'interviendront qu'après consignation des installations par du personnel possédant les habilitations H2B2/HCBC/BR.\nLes intervenants possèdent les habilitations nécessaires.",
            'eu' => $risques['electrique']['eu'] ?? true,
            'ee' => $risques['electrique']['ee'] ?? true,
        ])

        @include('pdf.partials.risque', [
            'situation' => "Utilisation de Produits chimiques dangereux",
            'applicable' => $risques['produits_chimiques']['applicable'] ?? false,
            'risque' => "Pollution\nIncompatibilité des produits\nIrritations\nMaladies Professionnelles",
            'mesure' => "Fournir à l'entreprise utilisatrice les FDS des produits utilisés et appliquer les recommandations",
            'eu' => $risques['produits_chimiques']['eu'] ?? false,
            'ee' => $risques['produits_chimiques']['ee'] ?? true,
        ])

        @include('pdf.partials.risque', [
            'situation' => "Intervention / changement des flexible d'engins (découpeuse, meuleuse à disque, desserrage)",
            'applicable' => $risques['flexibles_engins']['applicable'] ?? false,
            'risque' => "Projection d'huile. Étincelles, risque de descente d'engin.",
            'mesure' => "Balisage de la zone de travail.\nImmobilisation et verrouillage du bras de l'engin\nRespect du mode opératoire du changement de flexible\nEtablir un permis feu si activité par point chaud (annexe PDP)",
            'eu' => $risques['flexibles_engins']['eu'] ?? false,
            'ee' => $risques['flexibles_engins']['ee'] ?? true,
        ])
    </table>

</div>

<pagebreak />

{{-- ════════════════════════════════════════════════════════════════════════
     PAGE 5 — Fin tableau risques + Autres risques + Habilitations
     ════════════════════════════════════════════════════════════════════════ --}}
<div>

    <img src="{{ $assets }}logo-salti.png" style="width:80px;margin-bottom:6px">

    <table class="bordered risques" style="margin-top:6px">
        <tr>
            <th style="width:25%">Situation de travail concernée</th>
            <th class="col-applicable"></th>
            <th style="width:30%">Risques de l'interférence</th>
            <th style="width:30%">Mesures de prévention</th>
            <th class="col-resp">EU</th>
            <th class="col-resp">EE</th>
        </tr>

        @include('pdf.partials.risque', [
            'situation' => "Multi interventions",
            'applicable' => $risques['multi_interventions']['applicable'] ?? false,
            'risque' => "Superpositions des tâches",
            'mesure' => "Fournir le MOS avant toute intervention, organisation des entreprises à la VIC. Balisage des zones de travail",
            'eu' => $risques['multi_interventions']['eu'] ?? false,
            'ee' => $risques['multi_interventions']['ee'] ?? true,
        ])

        @include('pdf.partials.risque', [
            'situation' => "Contamination (Exposition, grippe, virus…)",
            'applicable' => $risques['contamination']['applicable'] ?? true,
            'risque' => "",
            'mesure' => "Se laver les mains régulièrement.\nÉternuer ou tousser dans le creux de son coude plutôt que dans ses mains\nSe moucher dans un mouchoir à usage unique\nPorter un masque et respecter les règles de distanciation si vous êtes atteint par un virus",
            'eu' => $risques['contamination']['eu'] ?? true,
            'ee' => $risques['contamination']['ee'] ?? true,
        ])
    </table>

    {{-- Autres risques --}}
    <table class="bordered" style="margin-top:10px">
        <tr><th colspan="5" class="title-yellow">Autres risques non préalablement cités</th></tr>
        <tr style="background:#FFC000">
            <th rowspan="2" style="width:25%; background:#FFC000">Situation de travail</th>
            <th rowspan="2" style="width:25%; background:#FFC000">Risques de l'interférence</th>
            <th rowspan="2" style="width:30%; background:#FFC000">Mesures de prévention</th>
            <th colspan="2" style="background:#FFC000; text-decoration:underline">À la charge de</th>
        </tr>
        <tr style="background:#FFC000">
            <th class="col-resp">EU</th>
            <th class="col-resp">EE</th>
        </tr>
        @foreach($autresRisques as $ar)
            <tr style="height:24px">
                <td>{{ $val($ar['situation'] ?? null) }}</td>
                <td>{{ $val($ar['risque'] ?? null) }}</td>
                <td>{{ $val($ar['mesure'] ?? null) }}</td>
                <td class="col-resp">{!! ($ar['eu'] ?? false) ? '<strong>X</strong>' : '' !!}</td>
                <td class="col-resp">{!! ($ar['ee'] ?? false) ? '<strong>X</strong>' : '' !!}</td>
            </tr>
        @endforeach
    </table>

    {{-- Habilitations --}}
    <table class="bordered" style="margin-top:10px">
        <tr><th colspan="3" class="title-yellow">Autorisations de conduite et habilitations que doivent posséder les salariés de l'entreprise extérieure</th></tr>
        <tr>
            <th style="width:40%; background:#FFC000">Salarié</th>
            <th style="width:35%; background:#FFC000">Habilitation / CACES*</th>
            <th style="width:25%; background:#FFC000">Date de validité</th>
        </tr>
        @php $habs = $intervenants->whereNotNull('habilitation')->take(3); $i = 0; @endphp
        @foreach($habs as $iv)
            @php $i++; @endphp
            <tr style="height:30px">
                <td>{{ $iv->nom_prenom }}</td>
                <td>{{ $iv->habilitation }}</td>
                <td>{{ $iv->habilitation_validity ? $iv->habilitation_validity->format('d/m/Y') : '' }}</td>
            </tr>
        @endforeach
        @for($j = $i; $j < 3; $j++)
            <tr style="height:30px"><td></td><td></td><td></td></tr>
        @endfor
    </table>

    <p style="font-size:7.5pt; margin-top:4px; font-style:italic">
        *Annexer et sauvegarder une copie des CACES et autorisations de conduite dans l'application QSE.
    </p>

</div>

<pagebreak />

{{-- ════════════════════════════════════════════════════════════════════════
     PAGE 6 — Attestation + Signatures
     ════════════════════════════════════════════════════════════════════════ --}}
<div>

    <img src="{{ $assets }}logo-salti.png" style="width:80px;margin-bottom:6px">

    <p style="font-style:italic">
        L'entreprise SALTI se réserve le droit d'arrêter les travaux en cas de manquement aux règles
        de sécurité, ou de renvoyer un opérateur en cas d'écarts de comportement.
    </p>

    <table class="bordered" style="margin-top:8px">
        <tr><th colspan="3" class="title-yellow">Attestation de prise de connaissance du Plan de Prévention</th></tr>
        <tr style="background:#FFC000">
            <th style="width:50%; background:#FFC000">Salarié intervenant (Nom, Prénom)</th>
            <th style="width:15%; background:#FFC000">Date</th>
            <th style="width:35%; background:#FFC000">Signature</th>
        </tr>
        @php $intvSlice = $intervenants->take(4); $iCount = 0; @endphp
        @foreach($intvSlice as $iv)
            @php $iCount++; @endphp
            <tr style="height:50px">
                <td>{{ $iv->nom_prenom }}</td>
                <td>{{ $iv->date_signature ? $iv->date_signature->format('d/m/Y') : '' }}</td>
                <td class="signature-canvas">
                    @if($iv->signature_data)
                        <img src="{{ $iv->signature_data }}">
                    @endif
                </td>
            </tr>
        @endforeach
        @for($k = $iCount; $k < 4; $k++)
            <tr style="height:50px"><td></td><td></td><td></td></tr>
        @endfor
    </table>

    <h2 style="text-align:center; margin-top:12px; text-decoration:underline; font-size:13pt">Signature des représentants</h2>
    <p style="text-align:center; font-size:8.5pt">
        Par l'Entreprise utilisatrice (SALTI) et par l'entreprise extérieure, pour prise en compte du présent plan de prévention
    </p>
    <p style="font-style:italic; font-size:8.5pt; text-align:center">
        « Le représentant de chaque entreprise intervenante s'engage :
    </p>
    <ul style="font-size:8.5pt; font-style:italic; margin-top:4px">
        <li>à informer ses salariés et sous-traitants amenés à intervenir sur le site de SALTI des risques encourus et mesures de prévention à respecter</li>
        <li>à faire respecter par son personnel les consignes de sécurité et les mesures de prévention définies</li>
        <li>à alerter SALTI en cas d'évolution de son intervention faisant apparaître de nouveaux risques ou de difficultés d'application des mesures décidées.</li>
    </ul>
    <p style="font-style:italic; font-size:8.5pt">
        Le plan de prévention sera mis à jour ou complété en fonction de l'évolution des travaux et risques ou de l'intervention d'une nouvelle entreprise ».
    </p>

    <table class="bordered eu-ee" style="margin-top:14px">
        <tr>
            <th style="width:50%">Entreprise utilisatrice (SALTI)</th>
            <th style="width:50%">Entreprise extérieure</th>
        </tr>
        <tr>
            <td style="height:120px; vertical-align:top; padding:8px">
                Prénom NOM : <strong>{{ $val($pdp->donneur_ordre_nom) }}</strong><br>
                Fonction : <strong>{{ $val($data['signature_salti_fonction'] ?? null) }}</strong><br>
                Date de signature :
                <strong>{{ $pdp->signed_by_salti_at ? $pdp->signed_by_salti_at->format('d/m/Y') : '' }}</strong>
                <div class="signature-canvas" style="margin-top:8px; height:60px">
                    @if(!empty($data['signature_salti']))
                        <img src="{{ $data['signature_salti'] }}">
                    @endif
                </div>
            </td>
            <td style="height:120px; vertical-align:top; padding:8px">
                Prénom NOM : <strong>{{ $val($data['ee']['responsable_prestations'] ?? null) }}</strong><br>
                Fonction : <strong>{{ $val($data['signature_ee_fonction'] ?? null) }}</strong><br>
                Date de signature :
                <strong>{{ $pdp->signed_by_prestataire_at ? $pdp->signed_by_prestataire_at->format('d/m/Y') : '' }}</strong>
                <div class="signature-canvas" style="margin-top:8px; height:60px">
                    @if(!empty($data['signature_ee']))
                        <img src="{{ $data['signature_ee'] }}">
                    @endif
                </div>
            </td>
        </tr>
    </table>

</div>

</body>
</html>

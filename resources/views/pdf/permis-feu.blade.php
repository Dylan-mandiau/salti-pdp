{{--
    Permis feu — template fidèle au modèle officiel SALTI (PR0103-bis)
    Pré-rempli avec les infos du PDP + ce que le presta a saisi (mode "online")
    OU laissé vierge dans les cases manuscrites (mode "paper")

    Vars : $pdp, $data, $assets, $pf (data.permis_feu)
--}}
@php
    $cb = fn($v) => !empty($v) ? '☒' : '☐';
    $val = fn($v) => $v !== null && $v !== '' ? $v : '';
    $date = function($v) {
        if (empty($v)) return '';
        try { return \Carbon\Carbon::parse($v)->format('d/m/Y'); }
        catch (\Throwable $e) { return $v; }
    };
    $intervenants = $pdp->intervenants;

    // Helper rendu d'une ligne du tableau Mise en sécurité / Moyens de prévention
    $row = fn(array $r) => [
        'a_faire' => $r['a_faire'] ?? null,
        'qui' => $r['qui'] ?? null,
        'fait' => $r['fait'] ?? null,
        'fait_le' => $r['fait_le'] ?? null,
    ];

    // Cellule "OUI / NON" : coche selon la valeur
    $oui_non = function($v) use ($cb) {
        return $cb($v === 'oui').' OUI &nbsp; '.$cb($v === 'non').' NON';
    };
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
    body { font-family: arial, sans-serif; font-size: 9pt; color: #000; line-height: 1.25; }
    h1 { font-size: 16pt; margin: 0; padding: 0; text-align: center; font-weight: bold; }
    p { margin: 0 0 4px 0; }
    table { border-collapse: collapse; width: 100%; }
    td, th { padding: 4px 6px; vertical-align: top; }
    .section-title { color: #FFC000; font-weight: bold; font-size: 10pt; margin-top: 8px; margin-bottom: 4px; }
    .section-title::before { content: "* "; color: #FFC000; }
    .field-line { border-bottom: 1px dotted #888; padding-bottom: 2px; min-height: 14px; }
    .filled { font-weight: bold; }
    .sig-table { border: 1px solid #000; }
    .sig-table th, .sig-table td { border: 1px solid #000; padding: 6px; }
    .sig-table th { background: #f0f0f0; font-size: 8.5pt; }
    .legend-table { border: 1px solid #000; }
    .legend-table th, .legend-table td { border: 1px solid #000; padding: 4px 6px; font-size: 8.5pt; }
    .legend-table th { background: #2c5e8a; color: #fff; text-align: center; }
</style>
</head>
<body>

<table style="width:100%;margin-bottom:8px"><tr>
    <td style="width:50%"><h1>PERMIS DE FEU</h1></td>
    <td style="text-align:right"><img src="{{ $assets }}logo-salti.png" style="height:50px"></td>
</tr></table>

<p style="font-size:8.5pt;font-style:italic;margin-bottom:8px">
    La délivrance de ce document sous-entend que l'ensemble des signataires (y compris l'employeur ou son représentant)
    s'est informé préalablement de la configuration des locaux concernés par les travaux par points chauds et de ceux
    situés à proximité, des substances qui y sont utilisées ou entreposées, des activités effectuées (risques particuliers)
    et s'est assuré du bon état du matériel devant être utilisé.
</p>

{{-- Bloc TRAVAUX (gauche) + Heures/Lieu/Entreprise (centre) + Plan préventoin (droite) --}}
<table style="width:100%">
    <tr>
        <td style="width:33%;vertical-align:top">
            <div class="section-title">TRAVAUX</div>
            <p><strong>● description du travail à effectuer :</strong></p>
            <p class="field-line filled">{{ $val($data['operation']['designation'] ?? null) }}</p>
            <p style="margin-top:6px"><strong>● selon le mode opératoire (référence) :</strong></p>
            <p class="field-line filled">{{ $val($pf['mode_operatoire'] ?? null) }}</p>
            <p style="margin-top:6px"><strong>● date de début :</strong></p>
            <p class="field-line filled">{{ $date($data['operation']['date_debut'] ?? null) }}</p>
            <p style="margin-top:6px"><strong>● date de fin (ou durée maximale) :</strong></p>
            <p class="field-line filled">
                @php
                    $dv = $data['operation']['duree_value'] ?? null;
                    $du = $data['operation']['duree_unit'] ?? null;
                    $duree_aff = $dv && $du
                        ? trim($dv.' '.$du)
                        : ($data['operation']['duree'] ?? '');
                    echo $duree_aff;
                @endphp
            </p>
        </td>
        <td style="width:34%;vertical-align:top;padding-left:10px">
            <p><strong>● heure de début :</strong> <span class="filled">{{ $val($data['operation']['plage_debut'] ?? null) }}</span>
               <strong>/ fin :</strong> <span class="filled">{{ $val($data['operation']['plage_fin'] ?? null) }}</span></p>
            <p style="margin-top:6px"><strong>● lieu :</strong></p>
            <p class="field-line filled">{{ $val($data['operation']['lieu'] ?? null) }}</p>
            <p style="margin-top:6px"><strong>● entreprise ou service exécutant les travaux :</strong></p>
            <p class="field-line filled">{{ $val($data['ee']['raison_sociale'] ?? null) }}</p>
            <p style="margin-top:6px"><strong>● liste des opérateurs autorisés :</strong></p>
            @if(! empty($pf['operateurs_autorises']))
                <p class="filled">{{ $pf['operateurs_autorises'] }}</p>
            @else
                @foreach($intervenants as $iv)
                    <p class="filled">· {{ $iv->nom_prenom }}</p>
                @endforeach
            @endif
        </td>
        <td style="width:33%;vertical-align:top;padding-left:10px">
            <p><strong>● Plan de prévention (référence) :</strong></p>
            <p class="field-line filled" style="font-family:monospace;font-size:8pt">{{ Str::limit($pdp->uuid, 25) }}</p>

            <div style="margin-top:10px;background:#f5f5f5;padding:6px;border:1px solid #ccc">
                <strong>Nouvelle validation obligatoire</strong>
                <p style="margin-top:4px">{{ $cb(false) }} si travaux par points chauds &gt; 1 jour ;<br>
                nom : <span class="field-line">_______________</span></p>
                <p style="margin-top:4px">{{ $cb(false) }} si travaux par points chauds couvrant un changement de poste ;<br>
                nom : <span class="field-line">_______________</span></p>
            </div>
        </td>
    </tr>
</table>

{{-- 3 colonnes : type de travaux + matériels + risques particuliers --}}
<table style="width:100%;margin-top:8px">
    <tr>
        <td style="width:33%;vertical-align:top">
            <div class="section-title">Type de travaux par points chauds</div>
            <p>{{ $cb($pf['travaux']['soudage'] ?? false) }} soudage</p>
            <p>{{ $cb($pf['travaux']['tronconnage'] ?? false) }} tronçonnage</p>
            <p>{{ $cb($pf['travaux']['decoupage'] ?? false) }} découpage</p>
            <p>{{ $cb($pf['travaux']['meulage'] ?? false) }} meulage</p>
            <p>{{ $cb(! empty($pf['travaux']['autre'])) }} <span class="filled">{{ $val($pf['travaux']['autre'] ?? null) }}</span></p>
        </td>
        <td style="width:33%;vertical-align:top;padding-left:10px">
            <div class="section-title">Matériels utilisés</div>
            <p>{{ $cb($pf['materiels']['poste_souder'] ?? false) }} poste à souder</p>
            <p>{{ $cb($pf['materiels']['chalumeau'] ?? false) }} chalumeau</p>
            <p>{{ $cb($pf['materiels']['laser'] ?? false) }} laser</p>
            <p>{{ $cb($pf['materiels']['tronconneuse'] ?? false) }} tronçonneuse</p>
            <p>{{ $cb(! empty($pf['materiels']['autre'])) }} <span class="filled">{{ $val($pf['materiels']['autre'] ?? null) }}</span></p>
        </td>
        <td style="width:34%;vertical-align:top;padding-left:10px">
            <div class="section-title">RISQUES PARTICULIERS</div>
            <p><strong>● Risques liés aux produits, aux procédés, aux stockages... :</strong></p>
            <p class="field-line filled" style="min-height:30px">{{ $val($pf['risques_particuliers'] ?? null) }}</p>
            <p style="margin-top:8px">{{ $cb($pf['zone_atex_presence'] ?? false) }} Présence de zones ATEX (type, étendue, produits…)</p>
            <p>{{ $cb($pf['zone_atex_proximite'] ?? false) }} Proximité de zones ATEX</p>
            @if(! empty($pf['zone_atex_details']))
                <p class="filled" style="font-size:8.5pt">{{ $pf['zone_atex_details'] }}</p>
            @endif
        </td>
    </tr>
</table>

<div class="section-title" style="margin-top:8px">Documents associés</div>
<table style="width:100%"><tr>
    <td>{{ $cb($pf['documents_associes']['autorisation_travail'] ?? false) }} autorisation de travail</td>
    <td>{{ $cb($pf['documents_associes']['permis_penetrer'] ?? false) }} permis de pénétrer</td>
    <td>{{ $cb($pf['documents_associes']['drpce'] ?? false) }} DRPCE</td>
    <td>{{ $cb($pf['documents_associes']['certificat_degazage'] ?? false) }} certificat de dégazage/inertage</td>
</tr></table>

<div class="section-title" style="margin-top:10px">MISE EN SÉCURITÉ</div>
<table class="legend-table">
    <thead>
        <tr>
            <th style="width:50%">&nbsp;</th>
            <th style="width:15%">À FAIRE ?<br>OUI / NON</th>
            <th style="width:20%">QUI ?</th>
            <th style="width:15%">FAIT ?<br>OUI/NON, LE :</th>
        </tr>
    </thead>
    <tbody>
        @foreach(\App\Models\Pdp::PERMIS_FEU_MISE_EN_SECURITE as $slug => $label)
            @php $r = $row($pf['mise_en_securite'][$slug] ?? []); @endphp
            <tr>
                <td>{{ $label }}</td>
                <td style="text-align:center;font-size:8.5pt">{!! $oui_non($r['a_faire']) !!}</td>
                <td class="filled">{{ $val($r['qui']) }}</td>
                <td style="text-align:center;font-size:8.5pt">
                    {!! $oui_non($r['fait']) !!}
                    @if($r['fait'] === 'oui' && $r['fait_le'])
                        <br><span class="filled">{{ $date($r['fait_le']) }}</span>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

<pagebreak />

<div class="section-title">MOYENS DE PRÉVENTION</div>
<table class="legend-table">
    <thead>
        <tr>
            <th style="width:50%">&nbsp;</th>
            <th style="width:15%">À FAIRE ?<br>OUI / NON</th>
            <th style="width:20%">QUI ?</th>
            <th style="width:15%">FAIT ?<br>OUI/NON, LE :</th>
        </tr>
    </thead>
    <tbody>
        @php
            $mp_details = [
                'protection_abords' => ['<strong>Protection des abords</strong>', '• écrans, panneaux<br>• bâches ignifugées<br>• eau (arrosage)<br>• sable<br>• absorbant'],
                'ventilation' => ['Ventilation mécanique forcée', null],
                'controle_atmosphere' => ['<strong>Contrôle d\'atmosphère</strong>', '• explosimétrie<br>• teneur en oxygène<br>• détecteur de gaz'],
                'lutte_incendie' => ['<strong>Moyens de lutte contre l\'incendie :</strong> <em>en plus de ceux dévoués normalement à cet effet</em>', '• extincteur<br>• RIA<br>• lance à incendie'],
                'materiel_atex' => ['Utilisation de matériel spécifique pour travailler en zone ATEX (marquage…)', null],
            ];
        @endphp
        @foreach($mp_details as $slug => [$title, $sub])
            @php $r = $row($pf['moyens_prevention'][$slug] ?? []); @endphp
            <tr>
                <td>
                    {!! $title !!}
                    @if($sub)<p>{!! $sub !!}</p>@endif
                </td>
                <td style="text-align:center;font-size:8.5pt">{!! $oui_non($r['a_faire']) !!}</td>
                <td class="filled">{{ $val($r['qui']) }}</td>
                <td style="text-align:center;font-size:8.5pt">
                    {!! $oui_non($r['fait']) !!}
                    @if($r['fait'] === 'oui' && $r['fait_le'])
                        <br><span class="filled">{{ $date($r['fait_le']) }}</span>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="section-title" style="margin-top:8px">SURVEILLANCE DE SÉCURITÉ</div>
<p>● <strong>Pendant les travaux ;</strong></p>
<p>nom : <span class="filled">{{ $val($pf['surveillance_pendant'] ?? null) }}</span> ; visa : ________</p>
<p style="margin-top:4px">● <strong>Après les travaux à partir de</strong>
    <span class="filled">{{ $val($pf['surveillance_apres_de'] ?? null) }}</span> h
    <strong>jusqu'à</strong>
    <span class="filled">{{ $val($pf['surveillance_apres_a'] ?? null) }}</span> h ;</p>
<p>nom : <span class="filled">{{ $val($pf['surveillance_apres_nom'] ?? null) }}</span> ; visa : ________</p>

<div class="section-title" style="margin-top:8px">ALERTE EN CAS D'INCENDIE OU D'ACCIDENT - EMPLACEMENT DES MOYENS D'ALERTE</div>
<p class="field-line filled" style="min-height:14px">{{ $val($pf['alerte_emplacement'] ?? null) }}</p>

<div class="section-title" style="margin-top:8px">NUMÉROS D'URGENCE</div>
<p>● Pompiers : <strong>{{ $val($pf['pompiers_tel'] ?? '18') }}</strong></p>
<p>● Personne à contacter en cas d'accident ou d'incendie : <span class="filled">{{ $val($pf['contact_accident_nom'] ?? null) }}</span></p>
<p>● Tél. : <span class="filled">{{ $val($pf['contact_accident_tel'] ?? null) }}</span></p>

<table class="sig-table" style="margin-top:10px">
    <thead>
        <tr>
            <th>Personnes ou services concernés</th>
            <th>Nom</th>
            <th>Qualité</th>
            <th>Signature</th>
        </tr>
    </thead>
    <tbody>
        <tr><td>Responsable des travaux EU</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
        <tr><td>Chargé de sécurité EU</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
        <tr><td>Responsable d'intervention EI</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
        <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
        <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
    </tbody>
</table>

<p style="margin-top:10px">Permis de feu délivré le : <span class="filled">{{ $date($pf['date_delivrance'] ?? null) }}</span></p>
<p style="margin-top:6px">Signature de l'employeur ou de son représentant qualifié :</p>
@if(! empty($pf['signed_by_employer']))
    <img src="{{ $pf['signed_by_employer'] }}" style="max-height:60px;margin-top:4px">
@endif

</body>
</html>

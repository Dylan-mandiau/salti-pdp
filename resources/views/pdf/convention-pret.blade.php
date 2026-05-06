{{--
    Convention de prêt de matériel — fidèle au modèle officiel SALTI (PR0102)
    Pré-remplie avec les infos du PDP (raison sociale, adresse, matériels, durée).
    Vars : $pdp, $data, $assets, $agency
--}}
@php
    $val = fn($v) => $v !== null && $v !== '' ? $v : '';
    $date = function ($v) {
        if (empty($v)) return '';
        try { return \Carbon\Carbon::parse($v)->format('d/m/Y'); }
        catch (\Throwable $e) { return $v; }
    };
    $materiels = collect($data['materiels_pretes'] ?? [])->filter(fn($m) => ! empty($m['designation']));
    $duree_value = $data['operation']['duree_value'] ?? null;
    $duree_unit = $data['operation']['duree_unit'] ?? null;

    // Helper de conversion en jours
    $toDays = function ($value, $unit) {
        return match (strtolower((string) $unit)) {
            'jour', 'jours' => (string) (int) $value,
            'semaine', 'semaines' => (string) ((int) $value * 7),
            'mois' => (string) ((int) $value * 30),
            'an', 'ans', 'année', 'années' => (string) ((int) $value * 365),
            default => trim($value.' '.$unit),
        };
    };

    // Convention typiquement en jours — on convertit
    $dureeJours = '';
    if ($duree_value && $duree_unit) {
        $dureeJours = $toDays($duree_value, $duree_unit);
    } elseif (! empty($data['operation']['duree'])) {
        // Fallback : la durée est stockée en string libre (ex: "2 jours", "3 semaines")
        // On parse pour extraire chiffre + unité.
        if (preg_match('/(\d+)\s*(jour|jours|semaine|semaines|mois|an|ans|année|années)/i', $data['operation']['duree'], $m)) {
            $dureeJours = $toDays($m[1], $m[2]);
        } else {
            $dureeJours = $data['operation']['duree'];
        }
    }
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
    body { font-family: arial, sans-serif; font-size: 10pt; color: #000; line-height: 1.4; }
    h1 { font-size: 16pt; margin: 0; padding: 0; text-align: center; font-weight: bold; }
    .subtitle { text-align: center; font-style: italic; font-size: 10pt; margin-bottom: 12px; }
    .ref-block { background: #efefef; text-align: center; font-size: 9pt; padding: 4px 8px; margin-bottom: 12px; font-style: italic; }
    table { border-collapse: collapse; width: 100%; }
    .parties { border: 1px solid #000; }
    .parties td { border: 1px solid #000; padding: 8px; vertical-align: top; }
    .filled { font-weight: bold; color: #000; }
    .article { font-weight: bold; margin-top: 12px; }
    p { margin: 0 0 6px 0; }
</style>
</head>
<body>

<table style="margin-bottom:6px"><tr>
    <td style="width:50%"><img src="{{ $assets }}logo-salti.png" style="height:50px"></td>
    <td style="text-align:right;font-size:8pt;color:#666">SALTI - service QSE — Version du 04/01/2023</td>
</tr></table>

<h1>Convention de prêt de matériel</h1>
<p class="subtitle">pour une utilisation dans l'agence et à annexer au Plan de Prévention.<br>
(faire un contrat de location classique si intervention hors agence)</p>

<div class="ref-block">
    <strong>Référence :</strong> PR0102 |
    <strong>Date d'application :</strong> 08/2018 |
    <strong>Mise à jour :</strong> version 3 le 04/01/2023<br>
    <strong>Auteur :</strong> service QSE | <strong>Personnes concernées :</strong> sous-traitant
</div>

<table class="parties">
    <tr>
        <td style="width:50%">
            <p>Entre,</p>
            <p style="margin-top:8px">
                SALTI, rue des Châteaux,<br>
                59700 Marcq-en-Baroeul,<br>
                représenté par <span class="filled">{{ $val($pdp->donneur_ordre_nom) }}</span>
                @if($agency && $agency->city)
                    <br>(Agence {{ $agency->city }})
                @endif
            </p>
        </td>
        <td style="width:50%">
            <p>Et,</p>
            <p style="margin-top:8px">
                la société <span class="filled">{{ $val($data['ee']['raison_sociale'] ?? null) }}</span><br>
                située à <span class="filled">{{ $val($data['ee']['address'] ?? null) }}</span><br>
                Siret : <span class="filled">{{ $val($data['ee']['siret'] ?? null) }}</span><br>
                responsable ou représentant de l'employeur :<br>
                <span class="filled">{{ $val($data['ee']['responsable_prestations'] ?? null) }}</span>
            </p>
        </td>
    </tr>
</table>

<p style="margin-top:12px">Il a été arrêté et convenu ce qui suit :</p>

<p class="article">Article 1</p>
<p>Le Prêteur prête à titre de prêt à usage, à l'Emprunteur, qui accepte, le ou les biens ci-après désignés :</p>
<div style="margin: 8px 0 8px 30px">
    @forelse($materiels as $mat)
        <p class="filled">- {{ $mat['designation'] }}</p>
    @empty
        <p>- ............................................................................................</p>
        <p>- ............................................................................................</p>
        <p>- ............................................................................................</p>
    @endforelse
</div>
<p>Le tout désigné ci-après "<em>Les biens prêtés</em>"</p>
<p style="margin-top:6px">La présente convention est conclue conformément aux articles 1875 et suivants du Code Civil, à l'exception, toutefois des dispositions de l'article 1890 dudit Code. Elle est conclue à titre gracieux.</p>

<p class="article">Article 2</p>
<p>L'Emprunteur s'oblige expressément à utiliser les biens prêtés conformément à leur destination selon les prescriptions techniques du constructeur du matériel et livret de bord, qu'il déclare avoir reçus et parfaitement connaître.</p>

<p class="article">Article 3</p>
<p>Le présent prêt est conclu pour une durée de <span class="filled">{{ $dureeJours ?: '............' }}</span> jours,
   à compter du <span class="filled">{{ $date($data['operation']['date_debut'] ?? null) ?: '............' }}</span>.</p>
<p>En conséquence, l'Emprunteur s'oblige à rendre au Prêteur les biens prêtés en bon état de fonctionnement et de propreté
   (y compris les prescriptions techniques et le livret de bord), soit dès qu'il n'en aura plus l'usage ci-dessus défini, soit au plus tard à la date précisée ci-dessus.</p>
<p>Une fiche d'état contradictoire du matériel sera établie avant mise à disposition et au moment de la restitution.</p>

<p class="article">Article 4</p>
<p>Le présent prêt est fait sous les garanties ordinaires et de droit en pareille matière et, en outre, aux conditions suivantes.</p>
<p>L'Emprunteur prendra les biens prêtés dans leur état au jour de l'entrée en jouissance, sans recours contre le Prêteur pour quelque cause que ce soit, et notamment pour mauvais état et vice apparent ou caché.</p>

<pagebreak />

<p>En tout état de cause, le Prêteur s'engage à ce que les biens prêtés soient conformes à la réglementation en vigueur.</p>
<p>Il veillera en bon père de famille à la garde et à la conservation des biens prêtés. Il veillera tant à la formation de son personnel qu'au respect des règles d'utilisation. À l'expiration de la durée convenue, il restituera immédiatement les biens prêtés.</p>
<p>Il ne devra aucune indemnité à raison de l'usure des biens prêtés résultant de leur usage normal et sans faute de sa part,</p>
<p>Dans le cas où la valeur des biens se trouverait diminuée par suite d'accident ou toute autre cause qui ne relèverait pas d'une utilisation normale, même sans aucune faute de l'Emprunteur, ou encore en cas de vol ou de perte, celui-ci devra indemniser le Prêteur de cette diminution de valeur.</p>
<p>En cas de dommages, l'emprunteur sera facturé du coût intégral de la remise en état des biens.</p>
<p>En cas de vol, de perte ou lorsque les coûts de remise en état sont supérieurs à la valeur des biens, l'emprunteur devra au prêteur une somme équivalente à la valeur de remplacement des biens, affectée d'une vétusté de 1 % par mois d'ancienneté, calculée de façon dégressive.</p>
<p>L'Emprunteur s'engage à renoncer à tout recours contre le Prêteur et ses assureurs.</p>
<p>L'Emprunteur s'engage à s'assurer en responsabilité civile pour tous les dommages qu'ils pourraient causer aux tiers ou au Prêteur, et notamment en RC Automobile pour tout dommage qu'il causerait lors de la conduite des biens prêtés.</p>
<p>L'Emprunteur fournira une attestation d'assurance au Prêteur avant la remise du matériel.</p>

<p class="article">Article 5</p>
<p>En cas d'inexécution totale ou partielle de ses obligations par l'Emprunteur, le Prêteur pourra résilier de plein droit le présent contrat, sans indemnité ni préavis, après une mise en demeure restée infructueuse pendant huit jours.</p>

<p class="article">Article 6</p>
<p>La présente convention n'entraîne la cession d'aucun droit de propriété intellectuelle.</p>
<p>L'Emprunteur s'engage à défendre le droit de propriété du Prêteur contre toute tentative d'appréhension ou d'inscription de sûretés sur les biens prêtés à l'initiative des créanciers de l'Emprunteur. Il s'engage à prendre en charge l'intégralité des coûts de procédures éventuellement nécessaires pour libérer les biens, sans délai.</p>

<p class="article">Article 7</p>
<p>La Loi française sera applicable. De convention expresse entre les parties, le Tribunal de Commerce de LILLE sera seul compétent, y compris en cas de pluralité de défendeurs ou d'appels en garantie.</p>

<p style="margin-top:16px">Fait à <span class="filled">Marcq-en-Baroeul</span>, en double exemplaires,</p>
<p>Le <span class="filled">{{ now()->format('d/m/Y') }}</span></p>

<p style="margin-top:8px"><strong>Signatures</strong></p>

<pagebreak />

<table class="parties" style="margin-top:20px">
    <tr>
        <td style="width:50%;height:120px">
            <p>SA SALTI, représenté par<br>
            <span class="filled">{{ $val($pdp->donneur_ordre_nom) }}</span></p>
            @if(! empty($data['signature_salti']))
                <img src="{{ $data['signature_salti'] }}" style="max-height:60px;margin-top:8px">
            @endif
        </td>
        <td style="width:50%;height:120px">
            <p>la société <span class="filled">{{ $val($data['ee']['raison_sociale'] ?? null) }}</span><br>
            représenté par <span class="filled">{{ $val($data['ee']['responsable_prestations'] ?? null) }}</span></p>
            @if(! empty($data['signature_ee']))
                <img src="{{ $data['signature_ee'] }}" style="max-height:60px;margin-top:8px">
            @endif
        </td>
    </tr>
</table>

</body>
</html>

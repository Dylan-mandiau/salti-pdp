<x-layouts.pdp title="Édition du PDP">
@php
    $isLocked = in_array($pdp->status, ['signed', 'archived', 'cancelled']);
    $data = $pdp->data;
    $risques = $data['risques'] ?? [];
    $epi = $data['epi'] ?? [];
    $autresRisques = $data['autres_risques'] ?? [];
    while (count($autresRisques) < 5) $autresRisques[] = [];
    $habilitations = $pdp->intervenants->whereNotNull('habilitation')->take(3)->values();
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="pdpWizard({{ $step }})">

    {{-- En-tête --}}
    <div class="flex justify-between items-start mb-4">
        <div>
            <a href="{{ route('dashboard') }}" class="text-sm text-gray-500 hover:text-gray-900">← Retour au tableau de bord</a>
            <h1 class="text-2xl font-bold mt-1">Plan de Prévention</h1>
            <p class="text-sm text-gray-500">
                {{ $data['ee']['raison_sociale'] ?? 'Prestataire à définir' }} ·
                {{ $data['operation']['designation'] ?? 'Opération à définir' }}
            </p>
        </div>
        <div class="text-right">
            <div class="text-xs text-gray-500">État</div>
            <div class="font-medium">
                @php
                    $labels = [
                        'draft' => 'Brouillon',
                        'awaiting_prestataire' => 'En attente prestataire',
                        'awaiting_validation' => 'À valider',
                        'corrections_requested' => 'Corrections demandées',
                        'awaiting_signatures' => 'À signer',
                        'signed' => '✓ Signé',
                        'archived' => 'Archivé',
                        'cancelled' => 'Annulé',
                    ];
                @endphp
                {{ $labels[$pdp->status] ?? $pdp->status }}
            </div>
            <div class="text-xs text-green-600 mt-2" x-text="lastSavedText" x-show="lastSavedText"></div>
        </div>
    </div>

    {{-- Petit indicateur silencieux sur les étapes 1-5 (juste un compteur) --}}
    @if($step !== 6 && ($validation['errors_count'] > 0 || $validation['warnings_count'] > 0))
        <div class="mb-3 text-xs text-gray-500 flex items-center gap-3">
            @if($validation['errors_count'] > 0)
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded bg-red-50 text-red-700">
                    🔴 {{ $validation['errors_count'] }} erreur(s) — détail à l'étape 6
                </span>
            @endif
            @if($validation['warnings_count'] > 0)
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded bg-orange-50 text-orange-700">
                    🟠 {{ $validation['warnings_count'] }} avertissement(s)
                </span>
            @endif
        </div>
    @endif

    {{-- Stepper --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-2 mb-6">
        <div class="flex overflow-x-auto">
            @foreach([
                1 => 'Infos générales',
                2 => 'Documents & secours',
                3 => 'EPI + Risques 1',
                4 => 'Risques 2',
                5 => 'Risques 3 + Habilitations',
                6 => 'Signatures',
            ] as $n => $label)
                <a href="?step={{ $n }}"
                   class="flex-1 text-center px-3 py-2 text-sm rounded mx-1 whitespace-nowrap
                          {{ $step === $n ? 'bg-salti-yellow text-black font-semibold' : 'text-gray-600 hover:bg-gray-50' }}">
                    {{ $n }}. {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- Contenu de l'étape --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">

        {{-- ════════════════════════════════════════════════════════════════
             ÉTAPE 1 : Informations générales
             ════════════════════════════════════════════════════════════════ --}}
        @if($step === 1)
            <h2 class="text-lg font-semibold mb-2">Informations générales</h2>
            <p class="text-sm text-gray-500 mb-6">Les champs marqués <span class="text-red-600">*</span> sont obligatoires.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Bloc EU --}}
                <div>
                    <h3 class="font-medium text-sm text-gray-700 mb-3 pb-2 border-b">Entreprise utilisatrice (SALTI)</h3>
                    <div class="space-y-3">
                        @php
                            $isQse = auth()->user()->isQseAdmin();
                            $agencyOptions = $agencies->mapWithKeys(fn($a) => [$a->city ?? $a->name => $a->city ?? $a->name])->toArray();
                            $defaultAgency = $data['eu']['agence'] ?? (auth()->user()->city ?? auth()->user()->name);
                        @endphp
                        @if($isQse)
                            @include('pdp.partials.input', ['name' => 'eu.agence', 'label' => 'Agence', 'value' => $defaultAgency, 'type' => 'select', 'options' => $agencyOptions, 'required' => true])
                        @else
                            @include('pdp.partials.input', ['name' => 'eu.agence', 'label' => 'Agence', 'value' => $defaultAgency, 'required' => true])
                        @endif
                        @include('pdp.partials.input', ['name' => 'eu.donneur_ordre', 'label' => 'Donneur d\'ordre', 'value' => $data['eu']['donneur_ordre'] ?? $pdp->donneur_ordre_nom, 'required' => true])
                        @include('pdp.partials.input', ['name' => 'eu.address', 'label' => 'Adresse', 'value' => $data['eu']['address'] ?? null])
                        @include('pdp.partials.input', ['name' => 'eu.phone', 'label' => 'Téléphone', 'value' => $data['eu']['phone'] ?? null, 'type' => 'tel', 'maxlength' => 20])
                    </div>
                </div>
                {{-- Bloc EE --}}
                <div>
                    <h3 class="font-medium text-sm text-gray-700 mb-3 pb-2 border-b">Entreprise extérieure</h3>
                    <div class="space-y-3">
                        @include('pdp.partials.input', ['name' => 'ee.raison_sociale', 'label' => 'Raison sociale', 'value' => $data['ee']['raison_sociale'] ?? null, 'required' => true])
                        @include('pdp.partials.input', ['name' => 'ee.responsable_prestations', 'label' => 'Responsable des prestations', 'value' => $data['ee']['responsable_prestations'] ?? null, 'required' => true])
                        @include('pdp.partials.input', ['name' => 'ee.address', 'label' => 'Adresse', 'value' => $data['ee']['address'] ?? null])
                        @include('pdp.partials.input', ['name' => 'ee.phone', 'label' => 'Téléphone', 'value' => $data['ee']['phone'] ?? null, 'type' => 'tel', 'maxlength' => 20])
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Travaux sous-traités ? <span class="text-red-600">*</span></label>
                            <div class="flex gap-4">
                                <label class="flex items-center"><input type="radio" name="ee.sous_traitance" value="oui" @change="save()" {{ ($data['ee']['sous_traitance'] ?? null) === 'oui' ? 'checked' : '' }} class="mr-2"> Oui</label>
                                <label class="flex items-center"><input type="radio" name="ee.sous_traitance" value="non" @change="save()" {{ ($data['ee']['sous_traitance'] ?? null) === 'non' ? 'checked' : '' }} class="mr-2"> Non</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <h3 class="font-medium text-sm text-gray-700 mb-3 pb-2 border-b mt-8">Nature de l'opération</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type d'opération <span class="text-red-600">*</span></label>
                    <div class="space-y-1">
                        <label class="flex items-center"><input type="radio" name="operation.type" value="ponctuelle" @change="save()" {{ ($data['operation']['type'] ?? null) === 'ponctuelle' ? 'checked' : '' }} class="mr-2"> Ponctuelle</label>
                        <label class="flex items-center"><input type="radio" name="operation.type" value="annuelle" @change="save()" {{ ($data['operation']['type'] ?? null) === 'annuelle' ? 'checked' : '' }} class="mr-2"> Annuelle</label>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Volume horaire <span class="text-red-600">*</span></label>
                    <div class="space-y-1">
                        <label class="flex items-center"><input type="radio" name="operation.volume" value="moins_400h" @change="save()" {{ ($data['operation']['volume'] ?? null) === 'moins_400h' ? 'checked' : '' }} class="mr-2"> Moins de 400 heures</label>
                        <label class="flex items-center"><input type="radio" name="operation.volume" value="plus_400h" @change="save()" {{ ($data['operation']['volume'] ?? null) === 'plus_400h' ? 'checked' : '' }} class="mr-2"> Plus de 400 heures (sur 12 mois)</label>
                    </div>
                </div>
                <div class="md:col-span-2">
                    <label class="flex items-center"><input type="checkbox" name="operation.travaux_dangereux" @change="save()" {{ ($data['operation']['travaux_dangereux'] ?? false) ? 'checked' : '' }} class="mr-2"> Travaux dangereux (arrêté du 19/03/93)</label>
                </div>
                @include('pdp.partials.input', ['name' => 'operation.designation', 'label' => 'Désignation', 'value' => $data['operation']['designation'] ?? null, 'required' => true])
                @include('pdp.partials.input', ['name' => 'operation.lieu', 'label' => 'Lieu (zone)', 'value' => $data['operation']['lieu'] ?? null, 'required' => true])
                @include('pdp.partials.input', ['name' => 'operation.date_debut', 'label' => 'Date de début', 'value' => $data['operation']['date_debut'] ?? null, 'type' => 'date', 'required' => true])
                @include('pdp.partials.input', [
                    'name' => 'operation.duree',
                    'label' => 'Durée prévisible',
                    'value' => ['value' => $data['operation']['duree_value'] ?? null, 'unit' => $data['operation']['duree_unit'] ?? null],
                    'type' => 'duree',
                    'required' => true,
                ])
                @include('pdp.partials.input', [
                    'name' => 'operation.plages_horaires',
                    'label' => 'Plages horaires',
                    'value' => ['debut' => $data['operation']['plage_debut'] ?? null, 'fin' => $data['operation']['plage_fin'] ?? null],
                    'type' => 'time-range',
                    'required' => true,
                ])
                @include('pdp.partials.input', ['name' => 'operation.nb_salaries', 'label' => 'Nombre de salariés affectés', 'value' => $data['operation']['nb_salaries'] ?? null, 'type' => 'number', 'required' => true])
            </div>

            <h3 class="font-medium text-sm text-gray-700 mb-3 pb-2 border-b mt-8">Inspection commune</h3>
            <p class="text-xs text-gray-500 mb-3">Obligatoire si l'opération > 400 heures (article R4513-1 du Code du travail)</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @include('pdp.partials.input', ['name' => 'inspection.date', 'label' => 'Date de l\'inspection', 'value' => $data['inspection']['date'] ?? null, 'type' => 'date'])
                @include('pdp.partials.input', ['name' => 'inspection.participants', 'label' => 'Participants à l\'inspection', 'value' => $data['inspection']['participants'] ?? null])
                <div class="md:col-span-2">
                    @include('pdp.partials.input', ['name' => 'inspection.informations_echangees', 'label' => 'Informations échangées et/ou documents communiqués', 'value' => $data['inspection']['informations_echangees'] ?? null, 'type' => 'textarea'])
                </div>
                <div class="md:col-span-2">
                    @include('pdp.partials.input', ['name' => 'inspection.zones_visitees', 'label' => 'Zones visitées', 'value' => $data['inspection']['zones_visitees'] ?? null])
                </div>
                <div class="md:col-span-2">
                    @include('pdp.partials.input', ['name' => 'inspection.observations_cssct', 'label' => 'Observations émises par le CSSCT', 'value' => $data['inspection']['observations_cssct'] ?? null, 'type' => 'textarea'])
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Locaux sociaux mis à disposition</label>
                    <div class="flex flex-wrap gap-4">
                        <label class="flex items-center"><input type="checkbox" name="inspection.locaux.vestiaires" @change="save()" {{ ($data['inspection']['locaux']['vestiaires'] ?? false) ? 'checked' : '' }} class="mr-2"> Vestiaires</label>
                        <label class="flex items-center"><input type="checkbox" name="inspection.locaux.sanitaires" @change="save()" {{ ($data['inspection']['locaux']['sanitaires'] ?? false) ? 'checked' : '' }} class="mr-2"> Sanitaires</label>
                        <label class="flex items-center"><input type="checkbox" name="inspection.locaux.refectoire" @change="save()" {{ ($data['inspection']['locaux']['refectoire'] ?? false) ? 'checked' : '' }} class="mr-2"> Réfectoire</label>
                    </div>
                </div>
            </div>
        @endif

        {{-- ════════════════════════════════════════════════════════════════
             ÉTAPE 2 : Documents échangés + Organisation des secours
             ════════════════════════════════════════════════════════════════ --}}
        @if($step === 2)
            <h2 class="text-lg font-semibold mb-4">Documents échangés et Organisation des secours</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div>
                    <h3 class="font-medium text-sm text-gray-700 mb-3 pb-2 border-b">Documents remis au sous-traitant</h3>
                    <div class="space-y-2">
                        <label class="flex items-start gap-2"><input type="checkbox" name="documents_remis_ee.plan_acces" @change="save()" {{ ($data['documents_remis_ee']['plan_acces'] ?? false) ? 'checked' : '' }} class="mt-0.5"> <span class="text-sm">Plan (accès, circulation, zone d'attente) avec modalités d'accès et de stationnement</span></label>
                        <label class="flex items-start gap-2"><input type="checkbox" name="documents_remis_ee.permis_feu" @change="save()" {{ ($data['documents_remis_ee']['permis_feu'] ?? false) ? 'checked' : '' }} class="mt-0.5"> <span class="text-sm">Permis feu</span></label>
                        <label class="flex items-start gap-2"><input type="checkbox" name="documents_remis_ee.convention_pret" @change="save()" {{ ($data['documents_remis_ee']['convention_pret'] ?? false) ? 'checked' : '' }} class="mt-0.5"> <span class="text-sm">Convention de prêt de matériel</span></label>
                    </div>
                </div>
                <div>
                    <h3 class="font-medium text-sm text-gray-700 mb-3 pb-2 border-b">Documents remis à SALTI</h3>
                    <div class="space-y-2">
                        <label class="flex items-start gap-2"><input type="checkbox" name="documents_remis_salti.autorisation_conduite" @change="save()" {{ ($data['documents_remis_salti']['autorisation_conduite'] ?? false) ? 'checked' : '' }} class="mt-0.5"> <span class="text-sm">Autorisation de conduite</span></label>
                        <label class="flex items-start gap-2"><input type="checkbox" name="documents_remis_salti.caces" @change="save()" {{ ($data['documents_remis_salti']['caces'] ?? false) ? 'checked' : '' }} class="mt-0.5"> <span class="text-sm">CACES</span></label>
                        <label class="flex items-start gap-2"><input type="checkbox" name="documents_remis_salti.habilitations" @change="save()" {{ ($data['documents_remis_salti']['habilitations'] ?? false) ? 'checked' : '' }} class="mt-0.5"> <span class="text-sm">Habilitations</span></label>
                    </div>
                </div>
            </div>

            <h3 class="font-medium text-sm text-gray-700 mb-3 pb-2 border-b">Organisation des secours</h3>
            <p class="text-xs text-gray-500 mb-4">Au moins une personne formée SST est présente sur chaque site. Boîte à pharmacie disponible. Numéros : 15 / 18 / 112.</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="font-medium text-sm text-gray-700 mb-2">Personne formée SST à l'agence</h4>
                    <div class="space-y-3">
                        @include('pdp.partials.input', ['name' => 'secours.sst_nom', 'label' => 'Nom', 'value' => $data['secours']['sst_nom'] ?? null])
                        @include('pdp.partials.input', ['name' => 'secours.sst_fonction', 'label' => 'Fonction', 'value' => $data['secours']['sst_fonction'] ?? null])
                    </div>
                </div>
                <div>
                    <h4 class="font-medium text-sm text-gray-700 mb-2">Responsable entreprise extérieure</h4>
                    <div class="space-y-3">
                        @include('pdp.partials.input', ['name' => 'secours.resp_ee_nom', 'label' => 'Nom', 'value' => $data['secours']['resp_ee_nom'] ?? null])
                        @include('pdp.partials.input', ['name' => 'secours.resp_ee_fonction', 'label' => 'Fonction', 'value' => $data['secours']['resp_ee_fonction'] ?? null])
                    </div>
                </div>
            </div>
        @endif

        {{-- ════════════════════════════════════════════════════════════════
             ÉTAPE 3 : EPI obligatoires + 4 premières lignes risques
             ════════════════════════════════════════════════════════════════ --}}
        @if($step === 3)
            <h2 class="text-lg font-semibold mb-2">EPI obligatoires + Premiers risques</h2>
            <p class="text-sm text-gray-500 mb-6">Cochez les EPI obligatoires sur le site, puis indiquez les risques applicables.</p>

            <h3 class="font-medium text-sm text-gray-700 mb-3 pb-2 border-b">EPI obligatoires sur le site</h3>
            <p class="text-xs text-gray-500 mb-3">Cliquez sur une carte pour cocher/décocher l'EPI.</p>
            <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-8 gap-3 mb-4">
                @foreach([
                    'chaussures' => ['Chaussures', '/pdf-assets/epi-chaussures.png'],
                    'gants'      => ['Gants', '/pdf-assets/epi-gants.png'],
                    'casque'     => ['Casque', '/pdf-assets/epi-casque.png'],
                    'lunettes'   => ['Lunettes', '/pdf-assets/epi-lunettes.png'],
                    'masque'     => ['Masque', '/pdf-assets/epi-masque.png'],
                    'auditives'  => ['Auditives', '/pdf-assets/epi-auditives.png'],
                    'gilet_hv'   => ['Gilet HV', '/pdf-assets/epi-gilet-hv.png'],
                    'harnais'    => ['Harnais', '/pdf-assets/epi-harnais.jpg'],
                ] as $key => [$label, $img])
                    <label class="cursor-pointer block">
                        <input type="checkbox" name="epi.{{ $key }}" @change="save()" {{ ($epi[$key] ?? false) ? 'checked' : '' }} class="sr-only peer">
                        <div class="flex flex-col items-center p-3 border-2 rounded-lg transition
                                    border-gray-200 hover:border-salti-yellow
                                    peer-checked:border-salti-yellow peer-checked:bg-salti-yellow/10 peer-checked:shadow-md">
                            <img src="{{ $img }}" alt="{{ $label }}" class="h-10 w-auto mb-1" style="max-width:60px">
                            <span class="text-xs text-center font-medium">{{ $label }}</span>
                        </div>
                    </label>
                @endforeach
            </div>
            @include('pdp.partials.input', ['name' => 'epi.autres', 'label' => 'Autres EPI (texte libre)', 'value' => $epi['autres'] ?? null, 'placeholder' => 'ex: Lunettes pour la phase de découpe'])

            <h3 class="font-medium text-sm text-gray-700 mb-3 pb-2 border-b mt-8">Risques d'interférence (1/3)</h3>
            <div class="space-y-3">
                @include('pdp.partials.risque-row', ['key' => 'arrivee_site', 'title' => 'Arrivée sur le site', 'description' => 'Se garer en marche arrière, balisage, accueil, raccordement aux réseaux, stockage, vitesse 20 km/h.', 'risque' => $risques['arrivee_site'] ?? [], 'obligatoire' => true])
                @include('pdp.partials.risque-row', ['key' => 'circulation_interne', 'title' => 'Circulation interne (véhicule, engin, à pied)', 'description' => 'Collision avec d\'autres véhicules ou piétons. Respect des règles de circulation et stationnement.', 'risque' => $risques['circulation_interne'] ?? [], 'obligatoire' => true])
                @include('pdp.partials.risque-row', ['key' => 'stationnement', 'title' => 'Stationnement, entreposage', 'description' => 'Collision, heurt de personnes, entrave aux secours. Pas devant moyens d\'extinction, issues de secours, transformateurs.', 'risque' => $risques['stationnement'] ?? []])
                @include('pdp.partials.risque-row', ['key' => 'sols_souilles', 'title' => 'Sols souillés', 'description' => 'Chute, glissade. Nettoyage et rangement de la zone de travail.', 'risque' => $risques['sols_souilles'] ?? []])
            </div>
        @endif

        {{-- ════════════════════════════════════════════════════════════════
             ÉTAPE 4 : 7 risques techniques
             ════════════════════════════════════════════════════════════════ --}}
        @if($step === 4)
            <h2 class="text-lg font-semibold mb-2">Risques techniques (2/3)</h2>
            <p class="text-sm text-gray-500 mb-6">Cochez les risques applicables à l'intervention.</p>

            <div class="space-y-3">
                @include('pdp.partials.risque-row', ['key' => 'travail_hauteur', 'title' => 'Travail en hauteur — Utilisation d\'une nacelle', 'description' => 'Chute d\'objets / personnes. Balisage, échelle (accès uniquement), attestations harnais & autorisation conduite.', 'risque' => $risques['travail_hauteur'] ?? []])
                @include('pdp.partials.risque-row', ['key' => 'levage_manutention', 'title' => 'Levage et manutention', 'description' => 'Balancement/décrochement, rupture d\'élingues. Balisage + autorisation de conduite.', 'risque' => $risques['levage_manutention'] ?? []])
                @include('pdp.partials.risque-row', ['key' => 'soudure_decoupe', 'title' => 'Soudure / Découpe', 'description' => 'Incendie, brûlures, projection. Interdit près des zones ATEX. EPI + balisage.', 'risque' => $risques['soudure_decoupe'] ?? []])
                @include('pdp.partials.risque-row', ['key' => 'dechets', 'title' => 'Déchets produits par l\'activité', 'description' => 'Pollution. Respect du tri. Interdit déversement réseau pluvial/usées.', 'risque' => $risques['dechets'] ?? []])
                @include('pdp.partials.risque-row', ['key' => 'electrique', 'title' => 'Intervention sur installations électriques', 'description' => 'Électrocution, incendie. Consignation des installations + habilitations H2B2/HCBC/BR obligatoires.', 'risque' => $risques['electrique'] ?? []])
                @include('pdp.partials.risque-row', ['key' => 'produits_chimiques', 'title' => 'Utilisation de produits chimiques dangereux', 'description' => 'Pollution, irritations, MP. Fournir les FDS à SALTI.', 'risque' => $risques['produits_chimiques'] ?? []])
                @include('pdp.partials.risque-row', ['key' => 'flexibles_engins', 'title' => 'Intervention sur flexibles d\'engins', 'description' => 'Projection d\'huile, étincelles, descente d\'engin. Balisage + immobilisation + permis feu si point chaud.', 'risque' => $risques['flexibles_engins'] ?? []])
            </div>
        @endif

        {{-- ════════════════════════════════════════════════════════════════
             ÉTAPE 5 : Multi/Contam + Autres risques + Habilitations
             ════════════════════════════════════════════════════════════════ --}}
        @if($step === 5)
            <h2 class="text-lg font-semibold mb-2">Risques (3/3) + Habilitations</h2>

            <div class="space-y-3 mb-8">
                @include('pdp.partials.risque-row', ['key' => 'multi_interventions', 'title' => 'Multi interventions', 'description' => 'Superpositions des tâches. Fournir le MOS, organiser à la VIC, baliser les zones.', 'risque' => $risques['multi_interventions'] ?? []])
                @include('pdp.partials.risque-row', ['key' => 'contamination', 'title' => 'Contamination (exposition, grippe, virus…)', 'description' => 'Lavage des mains, hygiène, masque si malade. Respect distanciation.', 'risque' => $risques['contamination'] ?? [], 'obligatoire' => true])
            </div>

            <h3 class="font-medium text-sm text-gray-700 mb-3 pb-2 border-b">Autres risques non préalablement cités</h3>
            <p class="text-xs text-gray-500 mb-4">Ajoutez ici les risques spécifiques à cette opération s'ils ne sont pas dans la liste ci-dessus.</p>
            <div class="overflow-x-auto">
                <table class="w-full border border-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Situation</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Risque</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Mesure</th>
                            <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 uppercase">EU</th>
                            <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 uppercase">EE</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($autresRisques as $i => $ar)
                            <tr>
                                <td class="px-2 py-2"><input type="text" name="autres_risques.{{ $i }}.situation" value="{{ $ar['situation'] ?? '' }}" placeholder="Situation" class="w-full border-0 text-sm focus:ring-0"></td>
                                <td class="px-2 py-2"><input type="text" name="autres_risques.{{ $i }}.risque" value="{{ $ar['risque'] ?? '' }}" placeholder="Risque" class="w-full border-0 text-sm focus:ring-0"></td>
                                <td class="px-2 py-2"><input type="text" name="autres_risques.{{ $i }}.mesure" value="{{ $ar['mesure'] ?? '' }}" placeholder="Mesure de prévention" class="w-full border-0 text-sm focus:ring-0"></td>
                                <td class="px-3 py-2 text-center"><input type="checkbox" name="autres_risques.{{ $i }}.eu" @change="save()" {{ ($ar['eu'] ?? false) ? 'checked' : '' }}></td>
                                <td class="px-3 py-2 text-center"><input type="checkbox" name="autres_risques.{{ $i }}.ee" @change="save()" {{ ($ar['ee'] ?? false) ? 'checked' : '' }}></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <h3 class="font-medium text-sm text-gray-700 mb-3 pb-2 border-b mt-8">Autorisations de conduite & Habilitations EE</h3>
            <p class="text-xs text-gray-500 mb-4">Liste des salariés du prestataire avec leurs habilitations. À gérer dans l'étape <a href="?step=6" class="underline text-salti-yellow-dark">Signatures</a> en ajoutant des intervenants.</p>
            @if($habilitations->count() > 0)
                <table class="w-full border border-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left">Salarié</th>
                            <th class="px-3 py-2 text-left">Habilitation / CACES</th>
                            <th class="px-3 py-2 text-left">Date validité</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($habilitations as $iv)
                            <tr>
                                <td class="px-3 py-2">{{ $iv->nom_prenom }}</td>
                                <td class="px-3 py-2">{{ $iv->habilitation }}</td>
                                <td class="px-3 py-2">{{ $iv->habilitation_validity?->format('d/m/Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-sm text-gray-500 italic">Aucune habilitation enregistrée pour l'instant.</p>
            @endif
        @endif

        {{-- ════════════════════════════════════════════════════════════════
             ÉTAPE 6 : Analyse de cohérence + Signatures
             ════════════════════════════════════════════════════════════════ --}}
        @if($step === 6)
            <h2 class="text-lg font-semibold mb-4">Analyse de cohérence</h2>
            <p class="text-sm text-gray-500 mb-4">Vérification automatique avant validation. Corrigez les erreurs 🔴 pour pouvoir signer.</p>

            {{-- Bilan global --}}
            @if($validation['errors_count'] === 0 && $validation['warnings_count'] === 0)
                <div class="mb-6 px-4 py-4 bg-green-50 border border-green-300 text-green-900 rounded-lg">
                    ✅ <strong>Aucune anomalie détectée</strong> — le PDP peut être validé et signé.
                </div>
            @else
                <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="px-4 py-3 rounded-lg border {{ $validation['errors_count'] > 0 ? 'bg-red-50 border-red-300' : 'bg-gray-50 border-gray-200 opacity-60' }}">
                        <div class="text-xs uppercase font-semibold {{ $validation['errors_count'] > 0 ? 'text-red-900' : 'text-gray-500' }}">🔴 Erreurs bloquantes</div>
                        <div class="text-3xl font-bold mt-1 {{ $validation['errors_count'] > 0 ? 'text-red-900' : 'text-gray-400' }}">{{ $validation['errors_count'] }}</div>
                    </div>
                    <div class="px-4 py-3 rounded-lg border {{ $validation['warnings_count'] > 0 ? 'bg-orange-50 border-orange-300' : 'bg-gray-50 border-gray-200 opacity-60' }}">
                        <div class="text-xs uppercase font-semibold {{ $validation['warnings_count'] > 0 ? 'text-orange-900' : 'text-gray-500' }}">🟠 Avertissements</div>
                        <div class="text-3xl font-bold mt-1 {{ $validation['warnings_count'] > 0 ? 'text-orange-900' : 'text-gray-400' }}">{{ $validation['warnings_count'] }}</div>
                    </div>
                    <div class="px-4 py-3 rounded-lg border {{ $validation['infos_count'] > 0 ? 'bg-blue-50 border-blue-300' : 'bg-gray-50 border-gray-200 opacity-60' }}">
                        <div class="text-xs uppercase font-semibold {{ $validation['infos_count'] > 0 ? 'text-blue-900' : 'text-gray-500' }}">🔵 Informations</div>
                        <div class="text-3xl font-bold mt-1 {{ $validation['infos_count'] > 0 ? 'text-blue-900' : 'text-gray-400' }}">{{ $validation['infos_count'] }}</div>
                    </div>
                </div>

                @if($validation['errors_count'] > 0)
                    <div class="mb-4 bg-white border-2 border-red-200 rounded-lg overflow-hidden">
                        <div class="px-4 py-2 bg-red-50 border-b border-red-200">
                            <div class="text-sm font-semibold text-red-900 uppercase tracking-wide">🔴 Erreurs bloquantes</div>
                        </div>
                        <ul class="divide-y divide-gray-100">
                            @foreach($validation['errors'] as $err)
                                <li class="px-4 py-2 text-sm text-gray-900 flex justify-between items-center gap-3">
                                    <span>{{ $err['message'] }}</span>
                                    @if($err['step'])
                                        <a href="?step={{ $err['step'] }}" class="text-xs whitespace-nowrap text-red-700 hover:underline font-medium">→ Étape {{ $err['step'] }}</a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($validation['warnings_count'] > 0)
                    <div class="mb-4 bg-white border-2 border-orange-200 rounded-lg overflow-hidden">
                        <div class="px-4 py-2 bg-orange-50 border-b border-orange-200">
                            <div class="text-sm font-semibold text-orange-900 uppercase tracking-wide">🟠 Avertissements (n'empêchent pas la validation)</div>
                        </div>
                        <ul class="divide-y divide-gray-100">
                            @foreach($validation['warnings'] as $warn)
                                <li class="px-4 py-2 text-sm text-gray-900 flex justify-between items-center gap-3">
                                    <span>{{ $warn['message'] }}</span>
                                    @if($warn['step'])
                                        <a href="?step={{ $warn['step'] }}" class="text-xs whitespace-nowrap text-orange-700 hover:underline font-medium">→ Étape {{ $warn['step'] }}</a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($validation['infos_count'] > 0)
                    <div class="mb-6 bg-white border-2 border-blue-200 rounded-lg overflow-hidden">
                        <div class="px-4 py-2 bg-blue-50 border-b border-blue-200">
                            <div class="text-sm font-semibold text-blue-900 uppercase tracking-wide">🔵 Informations</div>
                        </div>
                        <ul class="divide-y divide-gray-100">
                            @foreach($validation['infos'] as $info)
                                <li class="px-4 py-2 text-sm text-gray-700">{{ $info['message'] }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endif

            <hr class="my-6">

            <h2 class="text-lg font-semibold mb-4">Signatures</h2>

            @if($pdp->status === 'awaiting_signatures' || $pdp->status === 'signed')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Signature SALTI --}}
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h3 class="font-semibold mb-2">Représentant SALTI</h3>
                        <p class="text-sm text-gray-600 mb-3">{{ $pdp->donneur_ordre_nom }}</p>
                        @if($pdp->signed_by_salti_at)
                            <div class="bg-green-50 border border-green-200 text-green-800 p-3 rounded">
                                ✓ Signé le {{ $pdp->signed_by_salti_at->format('d/m/Y H:i') }}
                            </div>
                        @else
                            <form method="POST" action="{{ route('pdp.sign-salti', $pdp) }}">
                                @csrf
                                <input type="text" name="signature_fonction" placeholder="Votre fonction" required value="{{ $data['signature_salti_fonction'] ?? '' }}"
                                       class="w-full border border-gray-300 rounded px-3 py-2 mb-3 text-sm">
                                <canvas id="sig-salti" class="border-2 border-dashed border-gray-300 rounded w-full bg-white" height="160"></canvas>
                                <input type="hidden" name="signature_data" id="sig-salti-data">
                                <div class="flex gap-2 mt-2">
                                    <button type="button" onclick="clearSig('salti')" class="text-sm text-gray-600">Effacer</button>
                                    <button type="submit" onclick="return submitSig('salti')" class="ml-auto bg-salti-yellow text-black font-semibold px-4 py-2 rounded">Signer</button>
                                </div>
                            </form>
                        @endif
                    </div>

                    {{-- Signature EE --}}
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h3 class="font-semibold mb-2">Représentant Entreprise Extérieure</h3>
                        @if($pdp->signed_by_prestataire_at)
                            <p class="text-sm text-gray-600 mb-3">{{ $data['ee']['responsable_prestations'] ?? '—' }}</p>
                            <div class="bg-green-50 border border-green-200 text-green-800 p-3 rounded">
                                ✓ Signé le {{ $pdp->signed_by_prestataire_at->format('d/m/Y H:i') }}
                            </div>
                        @elseif($pdp->mode === 'presentiel')
                            <p class="text-sm text-gray-600 mb-3"><span class="inline-block bg-blue-50 border border-blue-200 text-blue-700 text-xs px-2 py-0.5 rounded">📱 Mode présentiel</span> Passez l'appareil au représentant.</p>
                            <form method="POST" action="{{ route('pdp.sign-ee-presentiel', $pdp) }}">
                                @csrf
                                <input type="text" name="signature_nom" placeholder="Nom et prénom du représentant" required value="{{ $data['ee']['responsable_prestations'] ?? '' }}" class="w-full border border-gray-300 rounded px-3 py-2 mb-2 text-sm">
                                <input type="text" name="signature_fonction" placeholder="Fonction" required value="{{ $data['signature_ee_fonction'] ?? '' }}" class="w-full border border-gray-300 rounded px-3 py-2 mb-3 text-sm">
                                <canvas id="sig-ee" class="border-2 border-dashed border-gray-300 rounded w-full bg-white" height="160"></canvas>
                                <input type="hidden" name="signature_data" id="sig-ee-data">
                                <div class="flex gap-2 mt-2">
                                    <button type="button" onclick="clearSig('ee')" class="text-sm text-gray-600">Effacer</button>
                                    <button type="submit" onclick="return submitSig('ee')" class="ml-auto bg-salti-yellow text-black font-semibold px-4 py-2 rounded">Signer</button>
                                </div>
                            </form>
                        @else
                            <p class="text-sm text-gray-600 mb-3">{{ $data['ee']['responsable_prestations'] ?? '—' }}</p>
                            <div class="text-sm text-gray-600">⏳ En attente de la signature du prestataire (à distance).</div>
                        @endif
                    </div>
                </div>

                @if($pdp->status === 'signed')
                    <div class="mt-6 flex gap-3">
                        <a href="{{ route('pdp.download', $pdp) }}" class="bg-salti-yellow hover:bg-salti-yellow-dark text-black font-semibold px-5 py-2.5 rounded">📥 Télécharger le PDF final</a>
                        <a href="{{ route('pdp.preview', $pdp) }}" target="_blank" class="border border-gray-300 hover:bg-gray-50 px-5 py-2.5 rounded">👁 Aperçu</a>
                    </div>
                @endif
            @else
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded">
                    Le PDP doit être validé avant la phase de signature. Vérifiez les étapes 1 à 5.
                </div>
                @if($pdp->status === 'draft' || $pdp->status === 'awaiting_validation')
                    @if($validation['can_sign'])
                        <form method="POST" action="{{ route('pdp.validate', $pdp) }}" class="mt-4">
                            @csrf
                            <button type="submit" class="bg-black text-white font-semibold px-5 py-2.5 rounded hover:bg-gray-800">
                                ✓ Valider et passer aux signatures
                            </button>
                        </form>
                    @else
                        <button type="button" disabled class="mt-4 bg-gray-300 text-gray-600 font-semibold px-5 py-2.5 rounded cursor-not-allowed">
                            🔒 Validation bloquée — {{ $validation['errors_count'] }} erreur(s) à corriger
                        </button>
                    @endif
                @endif
            @endif
        @endif

    </div>

    {{-- Navigation --}}
    <div class="flex justify-between items-center mt-6">
        @if($step > 1)
            <a href="?step={{ $step - 1 }}" class="text-gray-600 hover:text-gray-900 px-4 py-2">← Étape précédente</a>
        @else
            <span></span>
        @endif

        <div class="flex gap-2">
            <a href="{{ route('pdp.preview', $pdp) }}" target="_blank" class="border border-gray-300 hover:bg-gray-50 px-4 py-2 rounded text-sm">👁 Aperçu PDF</a>
            <a href="{{ route('pdp.download', $pdp) }}" class="border border-gray-300 hover:bg-gray-50 px-4 py-2 rounded text-sm">📥 Télécharger</a>
            @if($pdp->mode === 'distance' && $pdp->status === 'draft')
                <button @click="showSendModal = true" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded text-sm">✉ Envoyer au prestataire</button>
            @endif
        </div>

        @if($step < 6)
            <a href="?step={{ $step + 1 }}" class="bg-salti-yellow hover:bg-salti-yellow-dark text-black font-semibold px-4 py-2 rounded">Étape suivante →</a>
        @else
            <span></span>
        @endif
    </div>

    {{-- Modal "Envoyer au prestataire" --}}
    <div x-show="showSendModal" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="showSendModal = false">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-bold mb-4">Envoyer le lien au prestataire</h3>
            <form method="POST" action="{{ route('pdp.send', $pdp) }}">
                @csrf
                <label class="block text-sm font-medium text-gray-700 mb-1">Email du prestataire</label>
                <input type="email" name="prestataire_email" required value="{{ $pdp->prestataire->email ?? '' }}" class="w-full border border-gray-300 rounded px-3 py-2 mb-4">
                <p class="text-xs text-gray-500 mb-4">Le lien sera valable 7 jours. Le prestataire pourra remplir sa partie depuis n'importe quel appareil.</p>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showSendModal = false" class="px-4 py-2 text-gray-600">Annuler</button>
                    <button type="submit" class="bg-salti-yellow text-black font-semibold px-4 py-2 rounded">Envoyer le lien</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function pdpWizard(initialStep) {
        return {
            step: initialStep,
            lastSavedText: '',
            showSendModal: false,
            saveTimer: null,

            init() {
                document.querySelectorAll('input[name], select[name], textarea[name]').forEach(el => {
                    el.addEventListener('input', () => this.scheduleSave());
                });
            },

            scheduleSave() {
                clearTimeout(this.saveTimer);
                this.saveTimer = setTimeout(() => this.save(), 800);
            },

            async save() {
                const form = {};
                // 1. Champs simples (name = "a.b.c")
                document.querySelectorAll('input[name], select[name], textarea[name]').forEach(el => {
                    let path = el.name;

                    // Cas spéciaux pour les composants composés (durée + plages horaires)
                    // operation.duree_value / operation.duree_unit → composent operation.duree_value et operation.duree_unit en BDD
                    // operation.plages_horaires_debut / _fin → idem

                    const parts = path.split('.');
                    let cur = form;
                    for (let i = 0; i < parts.length - 1; i++) {
                        cur[parts[i]] = cur[parts[i]] || {};
                        cur = cur[parts[i]];
                    }
                    const leaf = parts[parts.length - 1];
                    if (el.type === 'checkbox') {
                        cur[leaf] = el.checked;
                    } else if (el.type === 'radio') {
                        if (el.checked) cur[leaf] = el.value;
                    } else {
                        cur[leaf] = el.value;
                    }
                });

                // Composition manuelle des champs composés vers leur clé "BDD"
                if (form.operation) {
                    // duree : split en duree_value / duree_unit
                    if (form.operation.duree_value !== undefined || form.operation.duree_unit !== undefined) {
                        // les sous-clés operation.duree_value / operation.duree_unit sont déjà en place
                    }
                    // plages_horaires : split en plage_debut / plage_fin
                    if (form.operation.plages_horaires_debut !== undefined) {
                        form.operation.plage_debut = form.operation.plages_horaires_debut;
                        delete form.operation.plages_horaires_debut;
                    }
                    if (form.operation.plages_horaires_fin !== undefined) {
                        form.operation.plage_fin = form.operation.plages_horaires_fin;
                        delete form.operation.plages_horaires_fin;
                    }
                }

                try {
                    const r = await fetch('{{ route('pdp.auto-save', $pdp) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ data: form })
                    });
                    const d = await r.json();
                    if (d.saved_at) this.lastSavedText = `Enregistré à ${d.saved_at}`;
                } catch (e) {
                    this.lastSavedText = '⚠ Erreur d\'enregistrement';
                }
            }
        }
    }

    // Signature pads
    const sigPads = {};
    document.addEventListener('DOMContentLoaded', () => {
        ['salti', 'ee'].forEach(role => {
            const canvas = document.getElementById('sig-' + role);
            if (!canvas) return;
            const ratio = window.devicePixelRatio || 1;
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext('2d').scale(ratio, ratio);
            sigPads[role] = new SignaturePad(canvas, { penColor: '#000' });
        });
    });
    function clearSig(role) { sigPads[role]?.clear(); }
    function submitSig(role) {
        if (!sigPads[role] || sigPads[role].isEmpty()) {
            alert('Veuillez signer avant de valider.');
            return false;
        }
        document.getElementById('sig-' + role + '-data').value = sigPads[role].toDataURL('image/png');
        return true;
    }
</script>
</x-layouts.pdp>

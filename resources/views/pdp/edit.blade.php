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

    {{-- Bandeau "lien magique" pour les PDP à distance (visible toujours, jusqu'à signature) --}}
    @if($pdp->mode === 'distance' && $pdp->magic_token && ! $pdp->signed_by_prestataire_at)
        @php
            $expired = $pdp->magic_token_expires_at && $pdp->magic_token_expires_at->isPast();
        @endphp
        <div class="mb-4 px-4 py-3 rounded-lg shadow-sm border
                    {{ $expired ? 'bg-red-50 border-red-300' : 'bg-blue-50 border-blue-200' }}">
            <div class="flex items-start gap-3 flex-wrap">
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-semibold {{ $expired ? 'text-red-900' : 'text-blue-900' }} uppercase tracking-wide mb-1">
                        🔗 Lien magique prestataire
                        @if($expired)
                            <span class="bg-red-200 text-red-900 px-2 py-0.5 rounded ml-1 normal-case">⚠ Expiré</span>
                        @else
                            <span class="text-xs normal-case font-normal text-blue-700 ml-1">
                                Valide jusqu'au {{ $pdp->magic_token_expires_at?->format('d/m/Y à H\hi') }}
                            </span>
                        @endif
                    </div>
                    <input type="text" readonly value="{{ $pdp->magicLinkUrl() }}"
                           onclick="this.select()"
                           class="w-full font-mono text-sm border border-gray-300 rounded px-3 py-2 bg-white">
                </div>
                <div class="flex gap-2 shrink-0">
                    <button type="button" onclick="copyMagicLink()" class="bg-black text-white px-3 py-2 rounded text-sm hover:bg-gray-800">
                        📋 Copier
                    </button>
                    <form method="POST" action="{{ route('pdp.regenerate-link', $pdp) }}"
                          onsubmit="return confirm('Régénérer un nouveau lien ? L'ancien lien deviendra inutilisable.');"
                          class="inline">
                        @csrf
                        <button type="submit" class="bg-orange-500 text-white px-3 py-2 rounded text-sm hover:bg-orange-600">
                            🔄 Régénérer
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <script>
            function copyMagicLink() {
                const link = "{{ $pdp->magicLinkUrl() }}";
                navigator.clipboard.writeText(link).then(() => alert('Lien copié !'));
            }
        </script>
    @endif

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

    {{-- Stepper : mobile = numéros + titre court / desktop = tous les libellés --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-2 mb-6">
        @php
            $steps = [
                1 => ['Infos générales', 'Infos'],
                2 => ['Documents & secours', 'Docs'],
                3 => ['EPI + Risques 1', 'EPI 1'],
                4 => ['Risques 2', 'Risq 2'],
                5 => ['Risques 3 + Habilitations', 'Hab.'],
                6 => ['Signatures', 'Signer'],
            ];
        @endphp
        {{-- Mobile : compact avec snap scroll --}}
        <div class="flex md:hidden overflow-x-auto snap-x snap-mandatory gap-1 pb-1">
            @foreach($steps as $n => [$long, $short])
                <a href="?step={{ $n }}"
                   class="snap-start shrink-0 flex flex-col items-center px-3 py-2 rounded min-w-[72px] text-center
                          {{ $step === $n ? 'bg-salti-yellow text-black font-semibold' : 'text-gray-600 hover:bg-gray-50' }}">
                    <span class="text-base font-bold">{{ $n }}</span>
                    <span class="text-[11px] mt-0.5">{{ $short }}</span>
                </a>
            @endforeach
        </div>
        {{-- Desktop : libellé complet --}}
        <div class="hidden md:flex overflow-x-auto">
            @foreach($steps as $n => [$long, $short])
                <a href="?step={{ $n }}"
                   class="flex-1 text-center px-3 py-2 text-sm rounded mx-1 whitespace-nowrap
                          {{ $step === $n ? 'bg-salti-yellow text-black font-semibold' : 'text-gray-600 hover:bg-gray-50' }}">
                    {{ $n }}. {{ $long }}
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
                        @include('pdp.partials.input', ['name' => 'ee.siret', 'label' => 'SIRET', 'value' => $data['ee']['siret'] ?? null, 'type' => 'siret', 'help' => 'Pré-remplir si connu — sinon le prestataire le complétera.'])
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

                    {{-- Matériels prêtés (apparaît si Convention cochée) --}}
                    @if($data['documents_remis_ee']['convention_pret'] ?? false)
                        <div class="mt-4 border border-gray-200 rounded-lg p-3 bg-gray-50" x-data>
                            <h4 class="font-medium text-sm mb-2">Matériels prêtés au prestataire <span class="text-red-600">*</span></h4>
                            <div id="materiels-list" class="space-y-2">
                                @php $materiels = $data['materiels_pretes'] ?? []; if (empty($materiels)) $materiels = [['designation' => '']]; @endphp
                                @foreach($materiels as $idx => $mat)
                                    <div data-mat-line class="flex gap-2 items-center">
                                        <input type="text" name="materiels_pretes.{{ $idx }}.designation" value="{{ $mat['designation'] ?? '' }}" placeholder="ex. Mini-pelle 2T" class="flex-1 border border-gray-300 rounded px-3 py-1.5 text-sm">
                                        <button type="button" onclick="this.parentElement.remove(); document.querySelector('[x-data]').__x.$data.save()" class="text-red-600 hover:text-red-800 text-lg">×</button>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" onclick="addMateriel()" class="mt-2 text-sm text-blue-600 hover:underline">+ Ajouter un matériel</button>
                            <p class="text-xs text-gray-500 mt-2">Si la convention est cochée mais aucun matériel listé → erreur bloquante.</p>
                        </div>
                        <script>
                            function addMateriel() {
                                const container = document.getElementById('materiels-list');
                                const idx = container.querySelectorAll('[data-mat-line]').length;
                                const div = document.createElement('div');
                                div.setAttribute('data-mat-line', '');
                                div.className = 'flex gap-2 items-center';
                                const input = document.createElement('input');
                                input.type = 'text';
                                input.name = `materiels_pretes.${idx}.designation`;
                                input.placeholder = 'ex. Mini-pelle 2T';
                                input.className = 'flex-1 border border-gray-300 rounded px-3 py-1.5 text-sm';
                                input.addEventListener('input', () => document.querySelector('[x-data]').__x.$data.save());
                                const btn = document.createElement('button');
                                btn.type = 'button';
                                btn.textContent = '×';
                                btn.className = 'text-red-600 hover:text-red-800 text-lg';
                                btn.addEventListener('click', () => { div.remove(); document.querySelector('[x-data]').__x.$data.save(); });
                                div.appendChild(input);
                                div.appendChild(btn);
                                container.appendChild(div);
                                input.focus();
                            }
                        </script>
                    @endif
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
            <p class="text-xs text-gray-500 mb-3">Touchez une carte pour cocher/décocher l'EPI.</p>
            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-8 gap-2 sm:gap-3 mb-4">
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

            {{-- Mobile : cards empilées --}}
            <div class="md:hidden space-y-4">
                @foreach($autresRisques as $i => $ar)
                    <div class="border border-gray-200 rounded-lg p-3 space-y-2">
                        <div class="text-xs font-semibold text-gray-500">Risque #{{ $i + 1 }}</div>
                        <input type="text" name="autres_risques.{{ $i }}.situation" value="{{ $ar['situation'] ?? '' }}" placeholder="Situation" class="w-full border border-gray-200 rounded px-2 py-1.5 text-sm">
                        <input type="text" name="autres_risques.{{ $i }}.risque" value="{{ $ar['risque'] ?? '' }}" placeholder="Risque" class="w-full border border-gray-200 rounded px-2 py-1.5 text-sm">
                        <input type="text" name="autres_risques.{{ $i }}.mesure" value="{{ $ar['mesure'] ?? '' }}" placeholder="Mesure de prévention" class="w-full border border-gray-200 rounded px-2 py-1.5 text-sm">
                        <div class="flex gap-4 text-sm">
                            <label class="flex items-center gap-2"><input type="checkbox" name="autres_risques.{{ $i }}.eu" @change="save()" {{ ($ar['eu'] ?? false) ? 'checked' : '' }}> EU (SALTI)</label>
                            <label class="flex items-center gap-2"><input type="checkbox" name="autres_risques.{{ $i }}.ee" @change="save()" {{ ($ar['ee'] ?? false) ? 'checked' : '' }}> EE (Presta)</label>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Desktop : tableau --}}
            <div class="hidden md:block overflow-x-auto">
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
            <p class="text-xs text-gray-500 mb-4">
                Liste des salariés du prestataire avec leurs habilitations. <strong>Vous pouvez corriger les erreurs ou supprimer un salarié</strong>
                (utile si une date d'habilitation est mal saisie ou expirée). Un salarié peut avoir plusieurs habilitations.
            </p>
            @php
                $allInterv = $pdp->intervenants()->orderBy('id')->get();
                $nbSalariesSalti = (int) ($data['operation']['nb_salaries'] ?? 0);
                // Pré-remplit le nombre de cartes pour matcher le 'Nombre de salariés affectés'
                // saisi à l'étape 1 (présentiel) ou rempli par le presta (distance).
                $saltiInterCount = max(1, $nbSalariesSalti, $allInterv->count());
            @endphp
            @if($nbSalariesSalti > 0 && $allInterv->count() < $nbSalariesSalti)
                <div class="mb-3 px-3 py-2 bg-blue-50 border border-blue-200 rounded text-xs text-blue-900">
                    💡 <strong>{{ $nbSalariesSalti }} salarié{{ $nbSalariesSalti > 1 ? 's' : '' }} affecté{{ $nbSalariesSalti > 1 ? 's' : '' }}</strong> ont été déclarés à l'étape 1 — {{ $saltiInterCount - $allInterv->count() }} carte{{ ($saltiInterCount - $allInterv->count()) > 1 ? 's' : '' }} prête{{ ($saltiInterCount - $allInterv->count()) > 1 ? 's' : '' }} à remplir ci-dessous.
                </div>
            @endif
            <div id="salti-intervenants" class="space-y-3">
                @for($i = 0; $i < $saltiInterCount; $i++)
                    @php $iv = $allInterv->get($i); @endphp
                    @if($iv)
                    @php $habList = $iv->habilitations_list; if (empty($habList)) $habList = [['code' => null, 'label' => '', 'validity' => null]]; @endphp
                    <div class="border border-gray-200 rounded-lg p-3 bg-gray-50" data-iv-card data-iv-id="{{ $iv->id }}">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-xs font-semibold text-gray-500">Salarié EE</div>
                            <button type="button" onclick="deleteSaltiIntervenant(this)"
                                    class="text-red-600 hover:text-red-800 text-sm border border-red-200 hover:bg-red-50 rounded px-2 py-0.5">🗑 Supprimer</button>
                        </div>
                        <input type="text" data-iv-field="nom_prenom" value="{{ $iv->nom_prenom }}"
                               placeholder="Nom Prénom" class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-medium mb-2">
                        <label class="flex items-center gap-2 text-xs text-gray-700 mb-3 cursor-pointer">
                            <input type="checkbox" data-iv-field="is_representant" {{ $iv->is_representant ? 'checked' : '' }}>
                            <span><strong>Représentant légal de l'EE</strong> — sa signature d'attestation servira aussi comme signature finale du PDP (1 seule signature au lieu de 2)</span>
                        </label>
                        <div class="text-xs font-semibold text-gray-600 mb-1">Habilitations :</div>
                        <div class="space-y-2" data-iv-hab-list>
                            @foreach($habList as $habItem)
                                <div data-hab-line class="grid grid-cols-1 md:grid-cols-[1fr_180px_40px] gap-2 md:items-center bg-white p-2 rounded border border-gray-200">
                                    <button type="button" onclick="openHabPicker(this)"
                                            class="w-full text-left border border-gray-300 rounded px-3 py-2 text-sm hover:border-salti-yellow hover:bg-yellow-50 flex items-center justify-between gap-2 min-h-[38px]">
                                        <span data-hab-display class="truncate {{ empty($habItem['label']) ? 'text-gray-400' : 'text-gray-900 font-medium' }}">
                                            {{ $habItem['label'] ?: '— Choisir une habilitation —' }}
                                        </span>
                                        <span class="text-gray-400 shrink-0 text-xs">▾</span>
                                    </button>
                                    <input type="hidden" data-hab-field="label" value="{{ $habItem['label'] ?? '' }}">
                                    <input type="hidden" data-hab-field="code" value="{{ $habItem['code'] ?? '' }}">
                                    <input type="date" data-hab-field="validity" value="{{ $habItem['validity'] ?? '' }}" required
                                           class="border border-gray-200 rounded px-2 py-1.5 text-sm invalid:border-red-400 invalid:bg-red-50" title="Date de fin de validité (obligatoire)">
                                    <button type="button" onclick="removeIvHabLine(this)" class="text-red-500 hover:text-red-700 text-xl justify-self-center" title="Retirer cette habilitation">×</button>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" onclick="addIvHabLine(this)"
                                class="mt-2 inline-flex items-center gap-1 px-2 py-1 border border-gray-300 rounded text-xs hover:bg-white hover:border-salti-yellow transition">
                            + Ajouter une habilitation
                        </button>
                    </div>
                    @else
                        {{-- Carte vide pré-remplie pour atteindre nb_salaries --}}
                        <div class="border border-gray-200 rounded-lg p-3 bg-gray-50" data-iv-card data-iv-id="">
                            <div class="flex items-center justify-between mb-2">
                                <div class="text-xs font-semibold text-gray-500">Salarié EE #{{ $i + 1 }}</div>
                                <button type="button" onclick="deleteSaltiIntervenant(this)"
                                        class="text-red-600 hover:text-red-800 text-sm border border-red-200 hover:bg-red-50 rounded px-2 py-0.5">🗑 Supprimer</button>
                            </div>
                            <input type="text" data-iv-field="nom_prenom" value=""
                                   placeholder="Nom Prénom" class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-medium mb-2">
                            <label class="flex items-center gap-2 text-xs text-gray-700 mb-3 cursor-pointer">
                                <input type="checkbox" data-iv-field="is_representant">
                                <span><strong>Représentant légal de l'EE</strong> — sa signature d'attestation servira aussi comme signature finale du PDP</span>
                            </label>
                            <div class="text-xs font-semibold text-gray-600 mb-1">Habilitations :</div>
                            <div class="space-y-2" data-iv-hab-list>
                                <div data-hab-line class="grid grid-cols-1 md:grid-cols-[1fr_180px_40px] gap-2 md:items-center bg-white p-2 rounded border border-gray-200">
                                    <button type="button" onclick="openHabPicker(this)"
                                            class="w-full text-left border border-gray-300 rounded px-3 py-2 text-sm hover:border-salti-yellow hover:bg-yellow-50 flex items-center justify-between gap-2 min-h-[38px]">
                                        <span data-hab-display class="truncate text-gray-400">— Choisir une habilitation —</span>
                                        <span class="text-gray-400 shrink-0 text-xs">▾</span>
                                    </button>
                                    <input type="hidden" data-hab-field="label" value="">
                                    <input type="hidden" data-hab-field="code" value="">
                                    <input type="date" data-hab-field="validity" value="" required
                                           class="border border-gray-200 rounded px-2 py-1.5 text-sm" title="Date de fin de validité (obligatoire)">
                                    <button type="button" onclick="removeIvHabLine(this)" class="text-red-500 hover:text-red-700 text-xl justify-self-center">×</button>
                                </div>
                            </div>
                            <button type="button" onclick="addIvHabLine(this)"
                                    class="mt-2 inline-flex items-center gap-1 px-2 py-1 border border-gray-300 rounded text-xs hover:bg-white hover:border-salti-yellow transition">
                                + Ajouter une habilitation
                            </button>
                        </div>
                    @endif
                @endfor
            </div>
            <button type="button" onclick="addSaltiIntervenant()"
                    class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 border border-gray-300 rounded text-sm hover:bg-gray-50 hover:border-salti-yellow transition">
                <span class="text-lg">+</span> Ajouter un salarié
            </button>

            {{-- Modal habilitations partagée (incluse 1 seule fois sur la page) --}}
            @include('pdp.partials.hab-picker-modal')

            {{-- Permis feu — partial unifié avec côté presta.
                 Visible si la case « Permis feu » a été cochée à l'étape 2.
                 Permet à SALTI de remplir lui-même en présentiel ou de corriger
                 le formulaire à distance. Convention data-path = save automatique
                 vers /pdp/{pdp}/auto-save (gérée par pdpWizard().save()). --}}
            @if($data['documents_remis_ee']['permis_feu'] ?? false)
                <div class="mt-6">
                    @include('pdp.partials.permis-feu-form', [
                        'pf' => $data['permis_feu'] ?? [],
                        'downloadUrl' => route('pdp.download.permis-feu', $pdp),
                        'audience' => 'salti',
                    ])
                </div>
            @endif

            {{-- ═══════════════════════════════════════════════════════════
                 Documents joints — uploads côté SALTI (présentiel / correction)
                 ═══════════════════════════════════════════════════════════ --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6 mt-6">
                <h2 class="font-semibold mb-2">📎 Documents joints au PDP</h2>
                <p class="text-sm text-gray-600 mb-4">
                    Uploadez ici les CACES, autorisations de conduite, habilitations, FDS, photos,
                    permis feu papier scanné, etc. Cette section est utile en présentiel (SALTI tient
                    la tablette) ou pour corriger / compléter en distance ce que le presta a transmis.
                </p>

                @php $allDocs = $pdp->documents()->orderBy('uploaded_by')->orderBy('type')->get(); @endphp

                {{-- Dropzone unique générique — type sélectionné juste avant l'upload --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Catégorie du document</label>
                        <select id="salti-upload-type" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                            <option value="caces">CACES</option>
                            <option value="autorisation_conduite">Autorisation de conduite</option>
                            <option value="habilitation">Habilitation</option>
                            <option value="permis_feu">Permis feu (papier scanné)</option>
                            <option value="fds">FDS / Fiche de sécurité</option>
                            <option value="plan_acces">Plan d'accès</option>
                            <option value="convention_pret">Convention de prêt</option>
                            <option value="autre" selected>Autre</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Libellé (facultatif)</label>
                        <input type="text" id="salti-upload-label" placeholder="ex. Caces R489 cat 3 — Tony"
                               class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                    </div>
                </div>
                <div id="salti-dropzone"
                     class="border-2 border-dashed border-gray-300 rounded-lg p-5 text-center cursor-pointer hover:border-salti-yellow hover:bg-salti-yellow/5 transition">
                    <div class="text-3xl mb-2">📂</div>
                    <div class="text-sm font-medium text-gray-700 mb-1">
                        <span class="text-salti-yellow-dark underline">Cliquer pour choisir</span> ou glissez vos fichiers ici
                    </div>
                    <div class="text-xs text-gray-500">PDF, JPG, PNG, DOCX — max 10 Mo par fichier</div>
                    <input type="file" id="salti-upload-input" multiple class="hidden" accept=".pdf,.jpg,.jpeg,.png,.docx,.doc">
                </div>

                {{-- Liste des documents existants (presta + SALTI) --}}
                <div class="mt-4 space-y-2" id="salti-files-list">
                    @forelse($allDocs as $doc)
                        @include('pdp.partials.salti-doc-row', ['doc' => $doc, 'pdp' => $pdp])
                    @empty
                        <p class="text-sm text-gray-500 italic" data-empty-state>Aucun document pour le moment.</p>
                    @endforelse
                </div>
            </div>
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
                {{-- Bloc obligation de consultation côté SALTI --}}
                @if(! $pdp->signed_by_salti_at)
                    <div class="bg-yellow-50 border border-yellow-300 rounded-lg p-4 mb-4" x-data="{ saltiConsulted: false }" x-init="window.saltiConsulted = $data">
                        <p class="text-sm font-medium mb-3">
                            ⚠ <strong>Avant de signer</strong>, consultez les documents que vous engagez SALTI à respecter :
                        </p>
                        <ul class="space-y-2 text-sm mb-3">
                            <li class="flex items-center justify-between gap-3">
                                <span>📋 Le Plan de Prévention</span>
                                <a href="{{ route('pdp.preview', $pdp) }}" target="_blank" class="text-blue-600 hover:underline whitespace-nowrap">👁 Consulter</a>
                            </li>
                            @if($data['documents_remis_ee']['plan_acces'] ?? false)
                                @if($pdp->agency?->access_plan_path)
                                    <li class="flex items-center justify-between gap-3">
                                        <span>🏢 Plan d'accès / circulation ({{ $pdp->agency->city ?? '' }})</span>
                                        <a href="{{ route('admin.agencies.download-plan', $pdp->agency) }}" target="_blank" class="text-blue-600 hover:underline whitespace-nowrap">👁 Consulter</a>
                                    </li>
                                @endif
                            @endif
                            @if($data['documents_remis_ee']['permis_feu'] ?? false)
                                <li class="flex items-center justify-between gap-3">
                                    <span>🔥 Permis feu pré-rempli</span>
                                    <a href="{{ route('pdp.download', $pdp) }}" target="_blank" class="text-blue-600 hover:underline whitespace-nowrap">👁 Consulter (joint au PDF)</a>
                                </li>
                            @endif
                            @if($data['documents_remis_ee']['convention_pret'] ?? false)
                                <li class="flex items-center justify-between gap-3">
                                    <span>📋 Convention de prêt de matériel</span>
                                    <a href="{{ route('pdp.download', $pdp) }}" target="_blank" class="text-blue-600 hover:underline whitespace-nowrap">👁 Consulter (joint au PDF)</a>
                                </li>
                            @endif
                        </ul>
                        <label class="flex items-start gap-2 cursor-pointer p-3 bg-white rounded border border-yellow-300">
                            <input type="checkbox" id="salti-consult-cb" class="mt-0.5">
                            <span class="text-sm font-medium">
                                J'ai lu et compris l'ensemble des documents ci-dessus, et je m'engage à les faire respecter.
                            </span>
                        </label>
                    </div>
                @endif

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
                                <canvas id="sig-salti" class="border-2 border-dashed border-gray-300 rounded w-full bg-white touch-none" style="min-height:240px;height:40vh;max-height:360px"></canvas>
                                <input type="hidden" name="signature_data" id="sig-salti-data">
                                <div class="flex gap-2 mt-2">
                                    <button type="button" onclick="clearSig('salti')" class="text-sm text-gray-600">Effacer</button>
                                    <button type="submit" onclick="return checkConsultedAndSign('salti')" class="ml-auto bg-salti-yellow text-black font-semibold px-4 py-2 rounded">Signer</button>
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

                            {{-- Bloc obligation de consultation côté EE en présentiel --}}
                            <div class="bg-yellow-50 border border-yellow-300 rounded-lg p-3 mb-3">
                                <p class="text-xs font-medium mb-2">
                                    ⚠ <strong>Avant de signer</strong>, le représentant EE doit consulter les documents qui l'engagent :
                                </p>
                                <ul class="space-y-1 text-xs mb-2">
                                    <li class="flex items-center justify-between gap-2">
                                        <span>📋 Le Plan de Prévention</span>
                                        <a href="{{ route('pdp.preview', $pdp) }}" target="_blank" class="text-blue-600 hover:underline whitespace-nowrap">👁 Consulter</a>
                                    </li>
                                    @if($pdp->agency?->access_plan_path && ($data['documents_remis_ee']['plan_acces'] ?? false))
                                        <li class="flex items-center justify-between gap-2">
                                            <span>🏢 Plan d'accès</span>
                                            <a href="{{ route('admin.agencies.download-plan', $pdp->agency) }}" target="_blank" class="text-blue-600 hover:underline whitespace-nowrap">👁 Consulter</a>
                                        </li>
                                    @endif
                                </ul>
                                <label class="flex items-start gap-2 cursor-pointer p-2 bg-white rounded border border-yellow-300">
                                    <input type="checkbox" id="ee-consult-cb" class="mt-0.5">
                                    <span class="text-xs font-medium">
                                        Le représentant EE a lu et compris l'ensemble des documents ci-dessus, et s'engage à les respecter.
                                    </span>
                                </label>
                            </div>

                            <form method="POST" action="{{ route('pdp.sign-ee-presentiel', $pdp) }}">
                                @csrf
                                <input type="text" name="signature_nom" placeholder="Nom et prénom du représentant" required value="{{ $data['ee']['responsable_prestations'] ?? '' }}" class="w-full border border-gray-300 rounded px-3 py-2 mb-2 text-sm">
                                <input type="text" name="signature_fonction" placeholder="Fonction" required value="{{ $data['signature_ee_fonction'] ?? '' }}" class="w-full border border-gray-300 rounded px-3 py-2 mb-3 text-sm">
                                <canvas id="sig-ee" class="border-2 border-dashed border-gray-300 rounded w-full bg-white touch-none" style="min-height:240px;height:40vh;max-height:360px"></canvas>
                                <input type="hidden" name="signature_data" id="sig-ee-data">
                                <div class="flex gap-2 mt-2">
                                    <button type="button" onclick="clearSig('ee')" class="text-sm text-gray-600">Effacer</button>
                                    <button type="submit" onclick="return checkConsultedAndSign('ee')" class="ml-auto bg-salti-yellow text-black font-semibold px-4 py-2 rounded">Signer</button>
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

    {{-- ═══════════════════════════════════════════════════════════════════
         Section Documents — visible à toutes les étapes
         Permet à SALTI de consulter/télécharger tous les fichiers liés au PDP
         (PDP principal, annexes pré-remplies, et uploads du prestataire)
         ═══════════════════════════════════════════════════════════════════ --}}
    @php
        $allUploads = $pdp->documents()->orderBy('uploaded_by')->orderBy('type')->get();
        $hasPermisFeu = $data['documents_remis_ee']['permis_feu'] ?? false;
        $hasConventionPret = $data['documents_remis_ee']['convention_pret'] ?? false;
        $hasPlanAcces = $pdp->agency?->access_plan_path && ($data['documents_remis_ee']['plan_acces'] ?? false);
    @endphp
    <details class="bg-white rounded-lg shadow-sm border border-gray-200 mt-6" open>
        <summary class="cursor-pointer p-4 font-semibold text-gray-800 hover:bg-gray-50 rounded-lg">
            📁 Documents — Plan de Prévention &amp; annexes
            <span class="text-xs font-normal text-gray-500 ml-1">({{ 1 + ($hasPermisFeu ? 1 : 0) + ($hasConventionPret ? 1 : 0) + ($hasPlanAcces ? 1 : 0) + $allUploads->count() }})</span>
        </summary>
        <ul class="divide-y divide-gray-200 border-t border-gray-200">
            {{-- PDP principal --}}
            <li class="flex items-center justify-between gap-3 px-4 py-3">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="text-2xl flex-shrink-0">📋</span>
                    <div class="min-w-0">
                        <div class="font-medium text-sm truncate">Plan de Prévention</div>
                        <div class="text-xs text-gray-500">
                            @if($pdp->status === 'signed' || $pdp->status === 'archived')
                                Signé par les 2 parties
                            @elseif($pdp->signed_by_salti_at && ! $pdp->signed_by_prestataire_at)
                                Signé SALTI — en attente prestataire
                            @elseif(! $pdp->signed_by_salti_at && $pdp->signed_by_prestataire_at)
                                Signé prestataire — en attente SALTI
                            @else
                                Version en cours
                            @endif
                        </div>
                    </div>
                </div>
                <div class="flex gap-1.5 flex-shrink-0">
                    <a href="{{ route('pdp.preview', $pdp) }}" target="_blank"
                       class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium px-3 py-1.5 rounded whitespace-nowrap">👁 <span class="hidden sm:inline">Consulter</span></a>
                    <a href="{{ route('pdp.download', $pdp) }}"
                       class="bg-salti-yellow hover:brightness-95 text-black text-sm font-semibold px-3 py-1.5 rounded whitespace-nowrap">📥 <span class="hidden sm:inline">Télécharger</span></a>
                </div>
            </li>

            {{-- Plan d'accès --}}
            @if($hasPlanAcces)
                <li class="flex items-center justify-between gap-3 px-4 py-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="text-2xl flex-shrink-0">🏢</span>
                        <div class="min-w-0">
                            <div class="font-medium text-sm truncate">Plan d'accès / circulation</div>
                            <div class="text-xs text-gray-500">Agence {{ $pdp->agency->city ?? $pdp->agency->name }}</div>
                        </div>
                    </div>
                    <div class="flex gap-1.5 flex-shrink-0">
                        <a href="{{ route('pdp.download.plan-acces', $pdp) }}?inline=1" target="_blank"
                           class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium px-3 py-1.5 rounded whitespace-nowrap">👁 <span class="hidden sm:inline">Consulter</span></a>
                        <a href="{{ route('pdp.download.plan-acces', $pdp) }}"
                           class="bg-salti-yellow hover:brightness-95 text-black text-sm font-semibold px-3 py-1.5 rounded whitespace-nowrap">📥 <span class="hidden sm:inline">Télécharger</span></a>
                    </div>
                </li>
            @endif

            {{-- Permis feu --}}
            @if($hasPermisFeu)
                <li class="flex items-center justify-between gap-3 px-4 py-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="text-2xl flex-shrink-0">🔥</span>
                        <div class="min-w-0">
                            <div class="font-medium text-sm truncate">Permis feu</div>
                            <div class="text-xs text-gray-500">Pré-rempli — formulaire PR0103-bis</div>
                        </div>
                    </div>
                    <div class="flex gap-1.5 flex-shrink-0">
                        <a href="{{ route('pdp.download.permis-feu', $pdp) }}?inline=1" target="_blank"
                           class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium px-3 py-1.5 rounded whitespace-nowrap">👁 <span class="hidden sm:inline">Consulter</span></a>
                        <a href="{{ route('pdp.download.permis-feu', $pdp) }}"
                           class="bg-salti-yellow hover:brightness-95 text-black text-sm font-semibold px-3 py-1.5 rounded whitespace-nowrap">📥 <span class="hidden sm:inline">Télécharger</span></a>
                    </div>
                </li>
            @endif

            {{-- Convention de prêt --}}
            @if($hasConventionPret)
                <li class="flex items-center justify-between gap-3 px-4 py-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="text-2xl flex-shrink-0">📋</span>
                        <div class="min-w-0">
                            <div class="font-medium text-sm truncate">Convention de prêt de matériel</div>
                            <div class="text-xs text-gray-500">Pré-remplie — formulaire PR0102</div>
                        </div>
                    </div>
                    <div class="flex gap-1.5 flex-shrink-0">
                        <a href="{{ route('pdp.download.convention-pret', $pdp) }}?inline=1" target="_blank"
                           class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium px-3 py-1.5 rounded whitespace-nowrap">👁 <span class="hidden sm:inline">Consulter</span></a>
                        <a href="{{ route('pdp.download.convention-pret', $pdp) }}"
                           class="bg-salti-yellow hover:brightness-95 text-black text-sm font-semibold px-3 py-1.5 rounded whitespace-nowrap">📥 <span class="hidden sm:inline">Télécharger</span></a>
                    </div>
                </li>
            @endif

            {{-- Documents uploadés par le prestataire --}}
            @forelse($allUploads as $doc)
                <li class="flex items-center justify-between gap-3 px-4 py-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="text-2xl flex-shrink-0">
                            @switch($doc->type)
                                @case('caces')📜@break
                                @case('autorisation_conduite')🚜@break
                                @case('habilitation')⚡@break
                                @case('permis_feu')🔥@break
                                @case('fds')🧪@break
                                @case('plan_acces')🏢@break
                                @case('convention_pret')📋@break
                                @default📄
                            @endswitch
                        </span>
                        <div class="min-w-0">
                            <div class="font-medium text-sm truncate">{{ $doc->original_filename }}</div>
                            <div class="text-xs text-gray-500">
                                @switch($doc->type)
                                    @case('caces')CACES @break
                                    @case('autorisation_conduite')Autorisation de conduite @break
                                    @case('habilitation')Habilitation @break
                                    @case('permis_feu')Permis feu (papier) @break
                                    @case('fds')FDS @break
                                    @case('plan_acces')Plan d'accès @break
                                    @case('convention_pret')Convention de prêt @break
                                    @default Autre @break
                                @endswitch
                                — {{ number_format($doc->size / 1024, 0) }} Ko — uploadé par {{ $doc->uploaded_by === 'prestataire' ? 'le prestataire' : 'SALTI' }}
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-1.5 flex-shrink-0">
                        <a href="{{ route('pdp.download.document', ['pdp' => $pdp, 'doc' => $doc->id]) }}?inline=1" target="_blank"
                           class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium px-3 py-1.5 rounded whitespace-nowrap">👁 <span class="hidden sm:inline">Consulter</span></a>
                        <a href="{{ route('pdp.download.document', ['pdp' => $pdp, 'doc' => $doc->id]) }}"
                           class="bg-salti-yellow hover:brightness-95 text-black text-sm font-semibold px-3 py-1.5 rounded whitespace-nowrap">📥 <span class="hidden sm:inline">Télécharger</span></a>
                    </div>
                </li>
            @empty
                <li class="px-4 py-3 text-xs text-gray-500 italic">Aucun document additionnel uploadé par le prestataire pour le moment.</li>
            @endforelse
        </ul>
    </details>

    {{-- Navigation : 2 layouts (desktop full / mobile compact + menu actions) --}}

    {{-- Desktop : tout sur une ligne --}}
    <div class="hidden md:flex justify-between items-center mt-6">
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

    {{-- Mobile : barre sticky en bas avec ← / Actions / → + menu actions --}}
    <div class="md:hidden h-20"></div> {{-- spacer pour la nav fixe --}}
    <div class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-lg z-40 px-2 py-2"
         x-data="{ actionsOpen: false }">
        <div class="flex items-center gap-2">
            @if($step > 1)
                <a href="?step={{ $step - 1 }}" class="flex-1 text-center py-2.5 border border-gray-300 rounded text-sm">← Préc.</a>
            @else
                <span class="flex-1"></span>
            @endif

            <button type="button" @click="actionsOpen = !actionsOpen"
                    class="flex-1 py-2.5 border border-gray-300 rounded text-sm bg-gray-50 hover:bg-gray-100">
                ⚙ Actions
            </button>

            @if($step < 6)
                <a href="?step={{ $step + 1 }}" class="flex-1 text-center py-2.5 bg-salti-yellow text-black font-semibold rounded text-sm">Suivant →</a>
            @else
                <span class="flex-1"></span>
            @endif
        </div>

        {{-- Sheet d'actions qui remonte du bas --}}
        <div x-show="actionsOpen" x-cloak
             @click.away="actionsOpen = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="absolute bottom-full left-0 right-0 mb-2 mx-2 bg-white border border-gray-200 rounded-lg shadow-xl overflow-hidden">
            <a href="{{ route('pdp.preview', $pdp) }}" target="_blank" class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-100">
                👁 Aperçu PDF (nouvel onglet)
            </a>
            <a href="{{ route('pdp.download', $pdp) }}" class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-100">
                📥 Télécharger le PDF
            </a>
            @if($pdp->mode === 'distance' && $pdp->status === 'draft')
                <button type="button" @click="showSendModal = true; actionsOpen = false"
                        class="block w-full text-left px-4 py-3 hover:bg-gray-50 text-purple-700 font-medium">
                    ✉ Envoyer au prestataire
                </button>
            @endif
        </div>
    </div>

    {{-- Modal "Générer le lien magique" --}}
    <div x-show="showSendModal" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="showSendModal = false">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-bold mb-2">Générer le lien magique</h3>
            <p class="text-sm text-gray-600 mb-4">
                Un lien d'accès unique sera créé pour le prestataire. Vous pourrez le copier-coller
                dans votre propre email, SMS ou messagerie.
            </p>
            <ul class="text-xs text-gray-500 mb-4 space-y-1">
                <li>• Validité : 7 jours</li>
                <li>• Utilisable depuis n'importe quel appareil (PC, mobile, tablette)</li>
                <li>• Régénérable à tout moment si le presta le perd</li>
            </ul>
            <form method="POST" action="{{ route('pdp.send', $pdp) }}">
                @csrf
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showSendModal = false" class="px-4 py-2 text-gray-600">Annuler</button>
                    <button type="submit" class="bg-salti-yellow text-black font-semibold px-4 py-2 rounded">🔗 Générer le lien</button>
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
                // Champs name=… (formulaire wizard) + champs data-path / data-cb-path
                // (partials Permis feu / Mise en sécurité — convention partagée presta)
                document.querySelectorAll('input[name], select[name], textarea[name], [data-path], [data-cb-path]').forEach(el => {
                    el.addEventListener('input', () => this.scheduleSave());
                    el.addEventListener('change', () => this.scheduleSave());
                    el.addEventListener('blur', () => this.saveNow()); // sauve immédiatement au focus-out
                });
            },

            scheduleSave() {
                clearTimeout(this.saveTimer);
                this.saveTimer = setTimeout(() => this.save(), 300); // debounce 300ms (alignement avec côté presta)
            },

            saveNow() {
                clearTimeout(this.saveTimer);
                this.save();
            },

            async save() {
                const form = {};
                const setPath = (path, value) => {
                    const parts = path.split('.');
                    let cur = form;
                    for (let i = 0; i < parts.length - 1; i++) {
                        cur[parts[i]] = cur[parts[i]] || {};
                        cur = cur[parts[i]];
                    }
                    cur[parts[parts.length - 1]] = value;
                };

                // 1. Champs simples (name = "a.b.c")
                document.querySelectorAll('input[name], select[name], textarea[name]').forEach(el => {
                    if (el.type === 'checkbox') setPath(el.name, el.checked);
                    else if (el.type === 'radio') { if (el.checked) setPath(el.name, el.value); }
                    else setPath(el.name, el.value);
                });

                // 2. Champs partagés avec le partial Permis feu : data-path = chemin direct
                document.querySelectorAll('[data-path]').forEach(el => {
                    setPath(el.dataset.path, el.value);
                });

                // 3. Checkboxes du partial : data-cb-path = chemin booléen
                document.querySelectorAll('[data-cb-path]').forEach(el => {
                    if (el.type === 'checkbox') {
                        setPath(el.dataset.cbPath, el.checked);
                    } else if (el.type === 'radio') {
                        if (el.checked) setPath(el.dataset.cbPath, el.value);
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

    // ─── CRUD intervenants côté SALTI (étape 5) — MULTI-HABILITATIONS ──────
    const PDP_ID = {{ $pdp->id }};
    const CSRF = document.querySelector('meta[name=csrf-token]').content;

    let ivSaveTimer = null;

    /** Récolte les habilitations de la carte sous forme [{code, label, validity}] */
    function collectHabilitations(card) {
        const habs = [];
        card.querySelectorAll('[data-iv-hab-list] [data-hab-line]').forEach(line => {
            const label = (line.querySelector('[data-hab-field="label"]')?.value || '').trim();
            if (! label) return;
            habs.push({
                code: line.querySelector('[data-hab-field="code"]')?.value || null,
                label,
                validity: line.querySelector('[data-hab-field="validity"]')?.value || null,
            });
        });
        return habs;
    }

    /** Sauvegarde une carte salarié (nom + habilitations + flag représentant) via l'API upsert. */
    async function saveSaltiIntervenantCard(card) {
        const nom = (card.querySelector('[data-iv-field="nom_prenom"]')?.value || '').trim();
        const habs = collectHabilitations(card);
        const isRep = card.querySelector('[data-iv-field="is_representant"]')?.checked || false;
        if (! nom && habs.length === 0) return; // rien à sauvegarder
        if (! nom) return; // exigence : nom requis pour persister

        const id = card.dataset.ivId || null;
        const payload = {
            id: id ? parseInt(id) : null,
            nom_prenom: nom,
            habilitations: habs,
            is_representant: isRep,
        };

        try {
            const r = await fetch(`/pdp/${PDP_ID}/intervenants`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify(payload),
            });
            const d = await r.json();
            if (d.id && ! id) card.dataset.ivId = d.id;
        } catch (e) { console.error(e); }
    }

    function debouncedSave(card) {
        clearTimeout(ivSaveTimer);
        ivSaveTimer = setTimeout(() => saveSaltiIntervenantCard(card), 300);
    }

    /** Listeners initiaux sur les cartes pré-rendues côté serveur */
    document.querySelectorAll('[data-iv-card]').forEach(wireSaltiIntervenantCard);

    function wireSaltiIntervenantCard(card) {
        // Inputs nom + dates + checkbox représentant : sauve sur change/blur
        card.querySelectorAll('[data-iv-field], [data-hab-field="validity"]').forEach(el => {
            ['change', 'blur', 'input'].forEach(ev => el.addEventListener(ev, () => debouncedSave(card)));
        });
        // Modal habilitations : sauve quand 'hab-changed' bubble depuis la ligne
        card.addEventListener('hab-changed', () => debouncedSave(card));
    }

    /** Construit une ligne habilitation (bouton modal + date + ✕) — version SALTI */
    function buildSaltiHabLine() {
        const wrap = document.createElement('div');
        wrap.setAttribute('data-hab-line', '');
        wrap.className = 'grid grid-cols-1 md:grid-cols-[1fr_180px_40px] gap-2 md:items-center bg-white p-2 rounded border border-gray-200';
        wrap.innerHTML = `
            <button type="button" onclick="openHabPicker(this)"
                    class="w-full text-left border border-gray-300 rounded px-3 py-2 text-sm hover:border-salti-yellow hover:bg-yellow-50 flex items-center justify-between gap-2 min-h-[38px]">
                <span data-hab-display class="truncate text-gray-400">— Choisir une habilitation —</span>
                <span class="text-gray-400 shrink-0 text-xs">▾</span>
            </button>
            <input type="hidden" data-hab-field="label" value="">
            <input type="hidden" data-hab-field="code" value="">
            <input type="date" data-hab-field="validity" value="" required class="border border-gray-200 rounded px-2 py-1.5 text-sm invalid:border-red-400 invalid:bg-red-50" title="Date de fin de validité (obligatoire)">
            <button type="button" onclick="removeIvHabLine(this)" class="text-red-500 hover:text-red-700 text-xl justify-self-center" title="Retirer cette habilitation">×</button>
        `;
        return wrap;
    }

    window.addIvHabLine = function(btn) {
        const card = btn.closest('[data-iv-card]');
        const list = card.querySelector('[data-iv-hab-list]');
        const line = buildSaltiHabLine();
        list.appendChild(line);
        // Wire la nouvelle ligne pour les events (la card a déjà le listener hab-changed)
        line.querySelectorAll('[data-hab-field="validity"]').forEach(el => {
            ['change', 'blur'].forEach(ev => el.addEventListener(ev, () => debouncedSave(card)));
        });
    };

    window.removeIvHabLine = function(btn) {
        const card = btn.closest('[data-iv-card]');
        const list = card.querySelector('[data-iv-hab-list]');
        const line = btn.closest('[data-hab-line]');
        if (list.querySelectorAll('[data-hab-line]').length <= 1) {
            // Garde la ligne, vide les valeurs
            line.querySelectorAll('input').forEach(i => i.value = '');
            const display = line.querySelector('[data-hab-display]');
            if (display) {
                display.textContent = '— Choisir une habilitation —';
                display.classList.remove('text-gray-900', 'font-medium');
                display.classList.add('text-gray-400');
            }
        } else {
            line.remove();
        }
        debouncedSave(card);
    };

    window.deleteSaltiIntervenant = async function(btn) {
        const card = btn.closest('[data-iv-card]');
        const id = card.dataset.ivId;
        if (id) {
            if (! confirm('Supprimer ce salarié du PDP ? Cette action est irréversible.')) return;
            const r = await fetch(`/pdp/${PDP_ID}/intervenants/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            });
            if (r.ok) card.remove();
        } else {
            card.remove();
        }
    };
    // Alias rétrocompat : ancien onclick dans le DOM legacy
    window.deleteIntervenant = function(id) {
        const card = document.querySelector(`[data-iv-card][data-iv-id="${id}"]`);
        if (card) {
            const fakeBtn = card.querySelector('[onclick*="deleteSaltiIntervenant"]');
            if (fakeBtn) fakeBtn.click();
        }
    };

    /** Construit une carte salarié vide (pour le bouton + Ajouter un salarié) */
    function buildSaltiEmployeeCard() {
        const card = document.createElement('div');
        card.className = 'border border-gray-200 rounded-lg p-3 bg-gray-50';
        card.setAttribute('data-iv-card', '');
        card.dataset.ivId = '';
        card.innerHTML = `
            <div class="flex items-center justify-between mb-2">
                <div class="text-xs font-semibold text-gray-500">Salarié EE</div>
                <button type="button" onclick="deleteSaltiIntervenant(this)" class="text-red-600 hover:text-red-800 text-sm border border-red-200 hover:bg-red-50 rounded px-2 py-0.5">🗑 Supprimer</button>
            </div>
            <input type="text" data-iv-field="nom_prenom" placeholder="Nom Prénom" class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-medium mb-2">
            <label class="flex items-center gap-2 text-xs text-gray-700 mb-3 cursor-pointer">
                <input type="checkbox" data-iv-field="is_representant">
                <span><strong>Représentant légal de l'EE</strong> — sa signature d'attestation servira aussi comme signature finale du PDP</span>
            </label>
            <div class="text-xs font-semibold text-gray-600 mb-1">Habilitations :</div>
            <div class="space-y-2" data-iv-hab-list></div>
            <button type="button" onclick="addIvHabLine(this)" class="mt-2 inline-flex items-center gap-1 px-2 py-1 border border-gray-300 rounded text-xs hover:bg-white hover:border-salti-yellow transition">+ Ajouter une habilitation</button>
        `;
        card.querySelector('[data-iv-hab-list]').appendChild(buildSaltiHabLine());
        wireSaltiIntervenantCard(card);
        return card;
    }

    window.addSaltiIntervenant = function() {
        const container = document.getElementById('salti-intervenants');
        const card = buildSaltiEmployeeCard();
        container.appendChild(card);
        card.querySelector('[data-iv-field="nom_prenom"]')?.focus();
    };

    // ─── Upload de documents côté SALTI (étape 5) ─────────────────────────
    (function() {
        const dz = document.getElementById('salti-dropzone');
        const input = document.getElementById('salti-upload-input');
        const typeSelect = document.getElementById('salti-upload-type');
        const labelInput = document.getElementById('salti-upload-label');
        const filesList = document.getElementById('salti-files-list');
        if (! dz || ! input) return;

        dz.addEventListener('click', () => input.click());
        dz.addEventListener('dragover', (e) => { e.preventDefault(); dz.classList.add('border-salti-yellow', 'bg-salti-yellow/10'); });
        dz.addEventListener('dragleave', () => dz.classList.remove('border-salti-yellow', 'bg-salti-yellow/10'));
        dz.addEventListener('drop', (e) => {
            e.preventDefault();
            dz.classList.remove('border-salti-yellow', 'bg-salti-yellow/10');
            for (const file of e.dataTransfer.files) saltiUploadFile(file);
        });
        input.addEventListener('change', () => {
            for (const file of input.files) saltiUploadFile(file);
            input.value = '';
        });

        async function saltiUploadFile(file) {
            if (file.size > 10 * 1024 * 1024) { alert(`Le fichier "${file.name}" dépasse 10 Mo.`); return; }
            const fd = new FormData();
            fd.append('file', file);
            fd.append('type', typeSelect?.value || 'autre');
            fd.append('label', labelInput?.value || file.name);

            const tempLine = document.createElement('div');
            tempLine.className = 'flex items-center gap-2 p-2 bg-blue-50 border border-blue-200 rounded text-xs';
            tempLine.innerHTML = `<span>⏳</span><span>Upload en cours : ${file.name}</span>`;
            filesList.appendChild(tempLine);
            // Retire l'état 'aucun document'
            filesList.querySelector('[data-empty-state]')?.remove();

            try {
                const r = await fetch(`/pdp/${PDP_ID}/document`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: fd,
                });
                const d = await r.json();
                tempLine.remove();
                if (! r.ok || d.error) {
                    alert('Erreur upload : ' + (d.error || r.statusText));
                    return;
                }
                // Construit la ligne
                const line = document.createElement('div');
                line.setAttribute('data-doc-id', d.id);
                line.className = 'flex items-center justify-between gap-3 p-3 bg-gray-50 border border-gray-200 rounded';
                const icons = { caces:'📜', autorisation_conduite:'🚜', habilitation:'⚡', permis_feu:'🔥', fds:'🧪', plan_acces:'🏢', convention_pret:'📋', autre:'📄' };
                const typeLbl = { caces:'CACES', autorisation_conduite:'Autorisation de conduite', habilitation:'Habilitation', permis_feu:'Permis feu', fds:'FDS', plan_acces:"Plan d'accès", convention_pret:'Convention de prêt', autre:'Autre' };
                const ic = icons[d.type] || '📄';
                const tl = typeLbl[d.type] || 'Autre';
                line.innerHTML = `
                    <div class="flex items-center gap-2 min-w-0 flex-1">
                        <span class="text-2xl shrink-0">${ic}</span>
                        <div class="min-w-0">
                            <div class="text-sm font-medium truncate" data-name></div>
                            <div class="text-xs text-gray-500">${tl} — ${(d.size/1024).toFixed(0)} Ko — uploadé par SALTI</div>
                        </div>
                    </div>
                    <div class="flex gap-1.5 shrink-0">
                        <a href="${d.download_url}?inline=1" target="_blank" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium px-3 py-1.5 rounded">👁 <span class="hidden sm:inline">Voir</span></a>
                        <a href="${d.download_url}" class="bg-salti-yellow hover:brightness-95 text-black text-sm font-semibold px-3 py-1.5 rounded">📥</a>
                        <button type="button" onclick="saltiDeleteDoc(${d.id})" class="bg-red-50 hover:bg-red-100 text-red-700 text-sm font-medium px-2 py-1.5 rounded border border-red-200">🗑</button>
                    </div>`;
                line.querySelector('[data-name]').textContent = d.filename;
                filesList.appendChild(line);
                // Reset le label pour le prochain upload (la catégorie reste sur la dernière sélection)
                if (labelInput) labelInput.value = '';
            } catch (e) {
                tempLine.remove();
                alert('Erreur upload : ' + e.message);
            }
        }

        window.saltiDeleteDoc = async function(docId) {
            if (! confirm('Supprimer ce document ? Cette action est irréversible.')) return;
            const r = await fetch(`/pdp/${PDP_ID}/document/${docId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            });
            if (r.ok) {
                document.querySelector(`#salti-files-list [data-doc-id="${docId}"]`)?.remove();
                // Réaffiche l'état 'aucun document' si la liste est vide
                if (! filesList.querySelector('[data-doc-id]')) {
                    const empty = document.createElement('p');
                    empty.className = 'text-sm text-gray-500 italic';
                    empty.setAttribute('data-empty-state', '');
                    empty.textContent = 'Aucun document pour le moment.';
                    filesList.appendChild(empty);
                }
            } else {
                alert('Erreur suppression');
            }
        };
    })();

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
    function checkConsultedAndSign(role) {
        const cbId = role === 'salti' ? 'salti-consult-cb' : 'ee-consult-cb';
        const cb = document.getElementById(cbId);
        if (cb && !cb.checked) {
            alert('Vous devez consulter les documents et cocher la case d\'engagement avant de signer.');
            return false;
        }
        return submitSig(role);
    }
</script>
</x-layouts.pdp>

<x-layouts.pdp title="Édition du PDP">
@php
    $isLocked = in_array($pdp->status, ['signed', 'archived', 'cancelled']);
    $data = $pdp->data;
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

    {{-- Panneau de validation / cohérence --}}
    @if($validation['errors_count'] > 0 || $validation['warnings_count'] > 0)
        <div x-data="{ open: false }" class="mb-4">
            <button type="button" @click="open = !open"
                    class="w-full flex items-center justify-between px-4 py-3 rounded-lg shadow-sm border
                           {{ $validation['errors_count'] > 0 ? 'bg-red-50 border-red-300 text-red-900' : 'bg-orange-50 border-orange-300 text-orange-900' }}
                           hover:bg-opacity-80 transition">
                <span class="font-medium text-sm">
                    @if($validation['errors_count'] > 0)
                        🔴 <strong>{{ $validation['errors_count'] }} erreur(s) bloquante(s)</strong>
                    @endif
                    @if($validation['warnings_count'] > 0)
                        @if($validation['errors_count'] > 0) · @endif
                        🟠 {{ $validation['warnings_count'] }} avertissement(s)
                    @endif
                    @if($validation['errors_count'] > 0)
                        — la validation et les signatures sont bloquées tant que les erreurs ne sont pas corrigées.
                    @endif
                </span>
                <span x-text="open ? '▲ Masquer' : '▼ Voir le détail'" class="text-xs"></span>
            </button>

            <div x-show="open" x-cloak
                 class="mt-2 bg-white border border-gray-200 rounded-lg overflow-hidden">

                @if($validation['errors_count'] > 0)
                    <div class="px-4 py-3 bg-red-50 border-b border-red-200">
                        <div class="text-xs font-semibold text-red-900 mb-2 uppercase tracking-wide">🔴 Erreurs bloquantes ({{ $validation['errors_count'] }})</div>
                        <ul class="space-y-1 text-sm text-red-900">
                            @foreach($validation['errors'] as $err)
                                <li>
                                    @if($err['step'])
                                        <a href="?step={{ $err['step'] }}" class="underline hover:text-red-700">
                                            [Étape {{ $err['step'] }}]
                                        </a>
                                    @endif
                                    {{ $err['message'] }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($validation['warnings_count'] > 0)
                    <div class="px-4 py-3 bg-orange-50 border-b border-orange-200">
                        <div class="text-xs font-semibold text-orange-900 mb-2 uppercase tracking-wide">🟠 Avertissements ({{ $validation['warnings_count'] }})</div>
                        <ul class="space-y-1 text-sm text-orange-900">
                            @foreach($validation['warnings'] as $warn)
                                <li>
                                    @if($warn['step'])
                                        <a href="?step={{ $warn['step'] }}" class="underline hover:text-orange-700">
                                            [Étape {{ $warn['step'] }}]
                                        </a>
                                    @endif
                                    {{ $warn['message'] }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($validation['infos_count'] > 0)
                    <div class="px-4 py-3 bg-blue-50">
                        <div class="text-xs font-semibold text-blue-900 mb-2 uppercase tracking-wide">🔵 Informations</div>
                        <ul class="space-y-1 text-sm text-blue-900">
                            @foreach($validation['infos'] as $info)
                                <li>
                                    @if($info['step'])
                                        <a href="?step={{ $info['step'] }}" class="underline hover:text-blue-700">
                                            [Étape {{ $info['step'] }}]
                                        </a>
                                    @endif
                                    {{ $info['message'] }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    @elseif($pdp->status !== 'signed')
        <div class="mb-4 px-4 py-3 bg-green-50 border border-green-300 text-green-900 rounded-lg shadow-sm text-sm">
            ✅ Aucune erreur détectée — vous pouvez valider et signer.
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

        {{-- ÉTAPE 1 : Informations générales --}}
        @if($step === 1)
            <h2 class="text-lg font-semibold mb-4">Informations générales</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Bloc EU --}}
                <div>
                    <h3 class="font-medium text-sm text-gray-700 mb-3 pb-2 border-b">Entreprise utilisatrice (SALTI)</h3>
                    <div class="space-y-3">
                        @php
                            $isQse = auth()->user()->isQseAdmin();
                            $agencyOptions = $agencies->mapWithKeys(fn($a) => [
                                $a->city ?? $a->name => $a->city ?? $a->name
                            ])->toArray();
                            $defaultAgency = $data['eu']['agence'] ?? (auth()->user()->city ?? auth()->user()->name);
                        @endphp

                        @if($isQse)
                            {{-- QSE central : dropdown de toutes les agences --}}
                            @include('pdp.partials.input', [
                                'name' => 'eu.agence',
                                'label' => 'Agence',
                                'value' => $defaultAgency,
                                'type' => 'select',
                                'options' => $agencyOptions,
                            ])
                        @else
                            {{-- Compte d'agence : pré-rempli avec son agence (modifiable) --}}
                            @include('pdp.partials.input', [
                                'name' => 'eu.agence',
                                'label' => 'Agence',
                                'value' => $defaultAgency,
                            ])
                        @endif

                        @include('pdp.partials.input', ['name' => 'eu.donneur_ordre', 'label' => 'Donneur d\'ordre', 'value' => $data['eu']['donneur_ordre'] ?? $pdp->donneur_ordre_nom])
                        @include('pdp.partials.input', ['name' => 'eu.address', 'label' => 'Adresse', 'value' => $data['eu']['address'] ?? null])
                        @include('pdp.partials.input', ['name' => 'eu.phone', 'label' => 'Téléphone', 'value' => $data['eu']['phone'] ?? null, 'type' => 'tel', 'maxlength' => 20])
                    </div>
                </div>
                {{-- Bloc EE --}}
                <div>
                    <h3 class="font-medium text-sm text-gray-700 mb-3 pb-2 border-b">Entreprise extérieure</h3>
                    <div class="space-y-3">
                        @include('pdp.partials.input', ['name' => 'ee.raison_sociale', 'label' => 'Raison sociale', 'value' => $data['ee']['raison_sociale'] ?? null])
                        @include('pdp.partials.input', ['name' => 'ee.responsable_prestations', 'label' => 'Responsable des prestations', 'value' => $data['ee']['responsable_prestations'] ?? null])
                        @include('pdp.partials.input', ['name' => 'ee.address', 'label' => 'Adresse', 'value' => $data['ee']['address'] ?? null])
                        @include('pdp.partials.input', ['name' => 'ee.phone', 'label' => 'Téléphone', 'value' => $data['ee']['phone'] ?? null, 'type' => 'tel', 'maxlength' => 20])
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Travaux sous-traités ?</label>
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
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type d'opération</label>
                    <div class="space-y-1">
                        <label class="flex items-center"><input type="radio" name="operation.type" value="ponctuelle" @change="save()" {{ ($data['operation']['type'] ?? null) === 'ponctuelle' ? 'checked' : '' }} class="mr-2"> Ponctuelle</label>
                        <label class="flex items-center"><input type="radio" name="operation.type" value="annuelle" @change="save()" {{ ($data['operation']['type'] ?? null) === 'annuelle' ? 'checked' : '' }} class="mr-2"> Annuelle</label>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Volume horaire</label>
                    <div class="space-y-1">
                        <label class="flex items-center"><input type="radio" name="operation.volume" value="moins_400h" @change="save()" {{ ($data['operation']['volume'] ?? null) === 'moins_400h' ? 'checked' : '' }} class="mr-2"> Moins de 400 heures</label>
                        <label class="flex items-center"><input type="radio" name="operation.volume" value="plus_400h" @change="save()" {{ ($data['operation']['volume'] ?? null) === 'plus_400h' ? 'checked' : '' }} class="mr-2"> Plus de 400 heures (sur 12 mois)</label>
                    </div>
                </div>
                <div class="md:col-span-2">
                    <label class="flex items-center"><input type="checkbox" name="operation.travaux_dangereux" @change="save()" {{ ($data['operation']['travaux_dangereux'] ?? false) ? 'checked' : '' }} class="mr-2"> Travaux dangereux (arrêté du 19/03/93)</label>
                </div>
                @include('pdp.partials.input', ['name' => 'operation.designation', 'label' => 'Désignation', 'value' => $data['operation']['designation'] ?? null])
                @include('pdp.partials.input', ['name' => 'operation.lieu', 'label' => 'Lieu (zone)', 'value' => $data['operation']['lieu'] ?? null])
                @include('pdp.partials.input', ['name' => 'operation.date_debut', 'label' => 'Date de début', 'value' => $data['operation']['date_debut'] ?? null, 'type' => 'date'])
                @include('pdp.partials.input', ['name' => 'operation.duree', 'label' => 'Durée prévisible', 'value' => $data['operation']['duree'] ?? null, 'placeholder' => 'ex. 2 jours'])
                @include('pdp.partials.input', ['name' => 'operation.plages_horaires', 'label' => 'Plages horaires', 'value' => $data['operation']['plages_horaires'] ?? null, 'placeholder' => 'ex. 08h-17h'])
                @include('pdp.partials.input', ['name' => 'operation.nb_salaries', 'label' => 'Nombre de salariés', 'value' => $data['operation']['nb_salaries'] ?? null, 'type' => 'number'])
            </div>
        @endif

        {{-- ÉTAPE 6 : Signatures --}}
        @if($step === 6)
            <h2 class="text-lg font-semibold mb-4">Signatures</h2>
            <p class="text-sm text-gray-600 mb-6">Une fois le PDP validé et complet, les deux représentants signent ci-dessous.</p>

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
                            <form method="POST" action="{{ route('pdp.sign-salti', $pdp) }}" id="sign-salti-form">
                                @csrf
                                <input type="text" name="signature_fonction" placeholder="Votre fonction" required
                                       class="w-full border border-gray-300 rounded px-3 py-2 mb-3">
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
                            <p class="text-sm text-gray-600 mb-3">
                                <span class="inline-block bg-blue-50 border border-blue-200 text-blue-700 text-xs px-2 py-0.5 rounded">📱 Mode présentiel</span>
                                Passez l'appareil au représentant pour qu'il signe.
                            </p>
                            <form method="POST" action="{{ route('pdp.sign-ee-presentiel', $pdp) }}">
                                @csrf
                                <input type="text" name="signature_nom" placeholder="Nom et prénom du représentant" required
                                       value="{{ $data['ee']['responsable_prestations'] ?? '' }}"
                                       class="w-full border border-gray-300 rounded px-3 py-2 mb-2 text-sm">
                                <input type="text" name="signature_fonction" placeholder="Fonction" required
                                       value="{{ $data['signature_ee_fonction'] ?? '' }}"
                                       class="w-full border border-gray-300 rounded px-3 py-2 mb-3 text-sm">
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
                        <a href="{{ route('pdp.download', $pdp) }}" target="_blank"
                           class="bg-salti-yellow hover:bg-salti-yellow-dark text-black font-semibold px-5 py-2.5 rounded">
                            📥 Télécharger le PDF final
                        </a>
                        <a href="{{ route('pdp.preview', $pdp) }}" target="_blank"
                           class="border border-gray-300 hover:bg-gray-50 px-5 py-2.5 rounded">
                            👁 Aperçu
                        </a>
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
                        <button type="button" disabled
                                class="mt-4 bg-gray-300 text-gray-600 font-semibold px-5 py-2.5 rounded cursor-not-allowed"
                                title="Corrigez les {{ $validation['errors_count'] }} erreur(s) bloquante(s) avant de valider">
                            🔒 Validation bloquée — {{ $validation['errors_count'] }} erreur(s) à corriger
                        </button>
                    @endif
                @endif
            @endif
        @endif

        {{-- Étapes 2 à 5 : placeholder pour l'instant --}}
        @if(in_array($step, [2, 3, 4, 5]))
            <div class="bg-blue-50 border border-blue-200 text-blue-800 p-4 rounded">
                <p class="font-medium">Étape {{ $step }} en cours d'implémentation.</p>
                <p class="text-sm mt-1">Les champs sont déjà présents en base de données et seront ajoutés à l'interface dans la suite.</p>
            </div>
        @endif

    </div>

    {{-- Navigation pas-à-pas --}}
    <div class="flex justify-between items-center mt-6">
        @if($step > 1)
            <a href="?step={{ $step - 1 }}" class="text-gray-600 hover:text-gray-900 px-4 py-2">← Étape précédente</a>
        @else
            <span></span>
        @endif

        <div class="flex gap-2">
            <a href="{{ route('pdp.preview', $pdp) }}" target="_blank"
               class="border border-gray-300 hover:bg-gray-50 px-4 py-2 rounded text-sm">
                👁 Aperçu PDF
            </a>
            @if($pdp->mode === 'distance' && $pdp->status === 'draft')
                <button @click="showSendModal = true"
                        class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded text-sm">
                    ✉ Envoyer au prestataire
                </button>
            @endif
        </div>

        @if($step < 6)
            <a href="?step={{ $step + 1 }}" class="bg-salti-yellow hover:bg-salti-yellow-dark text-black font-semibold px-4 py-2 rounded">Étape suivante →</a>
        @else
            <span></span>
        @endif
    </div>

    {{-- Modal "Envoyer au prestataire" --}}
    <div x-show="showSendModal" x-cloak
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
         @click.self="showSendModal = false">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-bold mb-4">Envoyer le lien au prestataire</h3>
            <form method="POST" action="{{ route('pdp.send', $pdp) }}">
                @csrf
                <label class="block text-sm font-medium text-gray-700 mb-1">Email du prestataire</label>
                <input type="email" name="prestataire_email" required
                       value="{{ $pdp->prestataire->email ?? '' }}"
                       class="w-full border border-gray-300 rounded px-3 py-2 mb-4">
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
                document.querySelectorAll('input[name], select[name], textarea[name]').forEach(el => {
                    const path = el.name.split('.');
                    let cur = form;
                    for (let i = 0; i < path.length - 1; i++) {
                        cur[path[i]] = cur[path[i]] || {};
                        cur = cur[path[i]];
                    }
                    if (el.type === 'checkbox') {
                        cur[path[path.length - 1]] = el.checked;
                    } else if (el.type === 'radio') {
                        if (el.checked) cur[path[path.length - 1]] = el.value;
                    } else {
                        cur[path[path.length - 1]] = el.value;
                    }
                });

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

    // Signature pad
    const sigPads = {};
    document.addEventListener('DOMContentLoaded', () => {
        ['salti', 'ee'].forEach(role => {
            const canvas = document.getElementById('sig-' + role);
            if (!canvas) return;
            // Resize for sharpness
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

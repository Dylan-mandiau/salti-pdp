<x-layouts.pdp title="Édition du PDP">
@php
    $isLocked = in_array($pdp->status, ['signed', 'archived', 'cancelled']);
    $data = $pdp->data;
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="pdpWizard({{ $step }})">

    {{-- En-tête --}}
    <div class="flex justify-between items-start mb-4">
        <div>
            <a href="{{ route('pdp.dashboard') }}" class="text-sm text-gray-500 hover:text-gray-900">← Retour au tableau de bord</a>
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
                        @include('pdp.partials.input', ['name' => 'eu.agence', 'label' => 'Agence', 'value' => $data['eu']['agence'] ?? auth()->user()->city])
                        @include('pdp.partials.input', ['name' => 'eu.donneur_ordre', 'label' => 'Donneur d\'ordre', 'value' => $data['eu']['donneur_ordre'] ?? $pdp->donneur_ordre_nom])
                        @include('pdp.partials.input', ['name' => 'eu.address', 'label' => 'Adresse', 'value' => $data['eu']['address'] ?? null])
                        @include('pdp.partials.input', ['name' => 'eu.phone', 'label' => 'Téléphone', 'value' => $data['eu']['phone'] ?? null])
                    </div>
                </div>
                {{-- Bloc EE --}}
                <div>
                    <h3 class="font-medium text-sm text-gray-700 mb-3 pb-2 border-b">Entreprise extérieure</h3>
                    <div class="space-y-3">
                        @include('pdp.partials.input', ['name' => 'ee.raison_sociale', 'label' => 'Raison sociale', 'value' => $data['ee']['raison_sociale'] ?? null])
                        @include('pdp.partials.input', ['name' => 'ee.responsable_prestations', 'label' => 'Responsable des prestations', 'value' => $data['ee']['responsable_prestations'] ?? null])
                        @include('pdp.partials.input', ['name' => 'ee.address', 'label' => 'Adresse', 'value' => $data['ee']['address'] ?? null])
                        @include('pdp.partials.input', ['name' => 'ee.phone', 'label' => 'Téléphone', 'value' => $data['ee']['phone'] ?? null])
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
                @include('pdp.partials.input', ['name' => 'operation.date_debut', 'label' => 'Date de début', 'value' => $data['operation']['date_debut'] ?? null])
                @include('pdp.partials.input', ['name' => 'operation.duree', 'label' => 'Durée prévisible', 'value' => $data['operation']['duree'] ?? null])
                @include('pdp.partials.input', ['name' => 'operation.plages_horaires', 'label' => 'Plages horaires', 'value' => $data['operation']['plages_horaires'] ?? null])
                @include('pdp.partials.input', ['name' => 'operation.nb_salaries', 'label' => 'Nombre de salariés', 'value' => $data['operation']['nb_salaries'] ?? null])
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
                        <p class="text-sm text-gray-600 mb-3">{{ $data['ee']['responsable_prestations'] ?? '—' }}</p>

                        @if($pdp->signed_by_prestataire_at)
                            <div class="bg-green-50 border border-green-200 text-green-800 p-3 rounded">
                                ✓ Signé le {{ $pdp->signed_by_prestataire_at->format('d/m/Y H:i') }}
                            </div>
                        @elseif($pdp->mode === 'presentiel')
                            <div class="text-sm text-gray-600 mb-3">En mode présentiel : passez l'écran au prestataire pour signature.</div>
                            {{-- TODO: bouton "Passer la main au prestataire" --}}
                        @else
                            <div class="text-sm text-gray-600">En attente de la signature du prestataire (à distance).</div>
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
                    <form method="POST" action="{{ route('pdp.validate', $pdp) }}" class="mt-4">
                        @csrf
                        <button type="submit" class="bg-black text-white font-semibold px-5 py-2.5 rounded">
                            ✓ Valider et passer aux signatures
                        </button>
                    </form>
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

<x-layouts.pdp title="Plan de prévention - Prestataire">
@php
    $data = $pdp->data;
    $isLocked = in_array($pdp->status, ['signed', 'archived', 'cancelled']);
@endphp

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{ lastSaved: '' }">

    {{-- En-tête prestataire --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center gap-3 mb-2">
            <span class="bg-salti-yellow text-black font-bold px-3 py-1 rounded text-sm">SALTI</span>
            <span class="text-sm text-gray-500">Plan de Prévention 2026</span>
        </div>
        <h1 class="text-xl font-bold">Bienvenue sur votre espace prestataire</h1>
        <p class="text-sm text-gray-600 mt-2">
            <strong>{{ $pdp->agency->name ?? 'SALTI' }}</strong> vous a partagé un plan de prévention à compléter.
            Toutes vos saisies sont enregistrées automatiquement — vous pouvez fermer cet onglet et revenir plus tard via le même lien.
        </p>
        <div class="text-xs text-green-600 mt-2" x-text="lastSaved" x-show="lastSaved"></div>
    </div>

    @if($pdp->status === 'awaiting_validation')
        <div class="bg-blue-50 border border-blue-200 text-blue-800 p-4 rounded mb-6">
            ✓ Vous avez soumis votre partie. SALTI doit maintenant valider avant la phase de signature.
        </div>
    @elseif($pdp->status === 'corrections_requested')
        <div class="bg-orange-50 border border-orange-200 text-orange-800 p-4 rounded mb-6">
            ⚠ SALTI a demandé des corrections. Merci de modifier votre saisie puis de re-soumettre.
        </div>
    @endif

    {{-- Récap partie SALTI (lecture seule) --}}
    <div class="bg-gray-50 rounded-lg border border-gray-200 p-4 mb-6">
        <h2 class="font-semibold mb-3 text-sm text-gray-700">📋 Informations renseignées par SALTI</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
            <div><span class="text-gray-500">Agence :</span> {{ $data['eu']['agence'] ?? '—' }}</div>
            <div><span class="text-gray-500">Donneur d'ordre :</span> {{ $pdp->donneur_ordre_nom }}</div>
            <div><span class="text-gray-500">Opération :</span> {{ $data['operation']['designation'] ?? '—' }}</div>
            <div><span class="text-gray-500">Lieu :</span> {{ $data['operation']['lieu'] ?? '—' }}</div>
            <div><span class="text-gray-500">Date début :</span> {{ $data['operation']['date_debut'] ?? '—' }}</div>
            <div><span class="text-gray-500">Durée :</span> {{ $data['operation']['duree'] ?? '—' }}</div>
        </div>
    </div>

    {{-- Formulaire prestataire --}}
    <div id="ee-form">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="font-semibold mb-4">Vos informations (Entreprise extérieure)</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Raison sociale</label>
                    <input type="text" data-path="ee.raison_sociale" value="{{ $data['ee']['raison_sociale'] ?? '' }}"
                           {{ $isLocked ? 'disabled' : '' }}
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Responsable des prestations</label>
                    <input type="text" data-path="ee.responsable_prestations" value="{{ $data['ee']['responsable_prestations'] ?? '' }}"
                           {{ $isLocked ? 'disabled' : '' }}
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
                    <input type="text" data-path="ee.address" value="{{ $data['ee']['address'] ?? '' }}"
                           {{ $isLocked ? 'disabled' : '' }}
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                    <input type="text" data-path="ee.phone" value="{{ $data['ee']['phone'] ?? '' }}"
                           {{ $isLocked ? 'disabled' : '' }}
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Travaux sous-traités ?</label>
                    <div class="flex gap-4 pt-2">
                        <label class="flex items-center"><input type="radio" name="ee.sous_traitance" value="oui" {{ ($data['ee']['sous_traitance'] ?? null) === 'oui' ? 'checked' : '' }} class="mr-2 ee-radio"> Oui</label>
                        <label class="flex items-center"><input type="radio" name="ee.sous_traitance" value="non" {{ ($data['ee']['sous_traitance'] ?? null) === 'non' ? 'checked' : '' }} class="mr-2 ee-radio"> Non</label>
                    </div>
                </div>
            </div>
        </div>

        @if(! $isLocked && ! $pdp->signed_by_prestataire_at)
            @if($pdp->status === 'awaiting_prestataire' || $pdp->status === 'corrections_requested')
                <form method="POST" action="{{ route('prestataire.submit', $token) }}"
                      onsubmit="return confirm('Soumettre votre partie à SALTI pour validation ?');"
                      class="flex justify-end">
                    @csrf
                    <button type="submit"
                            class="bg-salti-yellow hover:bg-salti-yellow-dark text-black font-semibold px-5 py-2.5 rounded">
                        Soumettre à SALTI →
                    </button>
                </form>
            @elseif($pdp->status === 'awaiting_signatures')
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="font-semibold mb-3">Votre signature</h2>
                    <form method="POST" action="{{ route('prestataire.sign', $token) }}">
                        @csrf
                        <input type="text" name="signature_fonction" placeholder="Votre fonction" required
                               class="w-full border border-gray-300 rounded px-3 py-2 mb-3">
                        <canvas id="sig-ee" class="border-2 border-dashed border-gray-300 rounded w-full bg-white" height="160"></canvas>
                        <input type="hidden" name="signature_data" id="sig-ee-data">
                        <div class="flex gap-2 mt-2">
                            <button type="button" onclick="sigEE.clear()" class="text-sm text-gray-600">Effacer</button>
                            <button type="submit" onclick="return submitSigEE()"
                                    class="ml-auto bg-salti-yellow text-black font-semibold px-4 py-2 rounded">Signer</button>
                        </div>
                    </form>
                </div>
            @endif
        @endif

        @if($pdp->signed_by_prestataire_at)
            <div class="bg-green-50 border border-green-200 text-green-800 p-4 rounded">
                ✓ Vous avez signé le {{ $pdp->signed_by_prestataire_at->format('d/m/Y à H:i') }}
            </div>
        @endif
    </div>

</div>

<script>
    const TOKEN = @json($token);
    const CSRF = document.querySelector('meta[name=csrf-token]').content;

    // Auto-save
    let saveTimer;
    document.querySelectorAll('[data-path], .ee-radio').forEach(el => {
        el.addEventListener('input', () => {
            clearTimeout(saveTimer);
            saveTimer = setTimeout(autoSave, 800);
        });
        el.addEventListener('change', () => {
            clearTimeout(saveTimer);
            saveTimer = setTimeout(autoSave, 800);
        });
    });

    async function autoSave() {
        const data = {};
        document.querySelectorAll('[data-path]').forEach(el => {
            const path = el.dataset.path.split('.');
            let cur = data;
            for (let i = 0; i < path.length - 1; i++) {
                cur[path[i]] = cur[path[i]] || {};
                cur = cur[path[i]];
            }
            cur[path[path.length - 1]] = el.value;
        });
        document.querySelectorAll('.ee-radio:checked').forEach(el => {
            const path = el.name.split('.');
            let cur = data;
            for (let i = 0; i < path.length - 1; i++) {
                cur[path[i]] = cur[path[i]] || {};
                cur = cur[path[i]];
            }
            cur[path[path.length - 1]] = el.value;
        });

        try {
            const r = await fetch(`/p/${TOKEN}/save`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify({ data })
            });
            const d = await r.json();
            const el = document.querySelector('[x-data]').__x.$data;
            if (d.saved_at) el.lastSaved = `Enregistré à ${d.saved_at}`;
        } catch (e) {
            console.error(e);
        }
    }

    // Signature pad EE
    let sigEE;
    document.addEventListener('DOMContentLoaded', () => {
        const canvas = document.getElementById('sig-ee');
        if (!canvas) return;
        const ratio = window.devicePixelRatio || 1;
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext('2d').scale(ratio, ratio);
        sigEE = new SignaturePad(canvas, { penColor: '#000' });
    });
    function submitSigEE() {
        if (!sigEE || sigEE.isEmpty()) {
            alert('Veuillez signer avant de valider.');
            return false;
        }
        document.getElementById('sig-ee-data').value = sigEE.toDataURL('image/png');
        return true;
    }
</script>
</x-layouts.pdp>

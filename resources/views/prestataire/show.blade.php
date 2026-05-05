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
                    <input type="tel" data-path="ee.phone" value="{{ $data['ee']['phone'] ?? '' }}"
                           {{ $isLocked ? 'disabled' : '' }}
                           maxlength="20"
                           autocomplete="tel"
                           placeholder="06 12 34 56 78"
                           class="pdp-tel-input w-full border border-gray-300 rounded px-3 py-2 text-sm">
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

        {{-- Documents que vous remettez à SALTI --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="font-semibold mb-4">Documents que vous remettez à SALTI</h2>
            <p class="text-sm text-gray-600 mb-3">Cochez les documents fournis pour cette intervention.</p>
            <div class="space-y-2">
                <label class="flex items-start gap-2 cursor-pointer">
                    <input type="checkbox" data-cb-path="documents_remis_salti.autorisation_conduite" {{ ($data['documents_remis_salti']['autorisation_conduite'] ?? false) ? 'checked' : '' }} class="mt-0.5">
                    <span class="text-sm">Autorisation de conduite</span>
                </label>
                <label class="flex items-start gap-2 cursor-pointer">
                    <input type="checkbox" data-cb-path="documents_remis_salti.caces" {{ ($data['documents_remis_salti']['caces'] ?? false) ? 'checked' : '' }} class="mt-0.5">
                    <span class="text-sm">CACES</span>
                </label>
                <label class="flex items-start gap-2 cursor-pointer">
                    <input type="checkbox" data-cb-path="documents_remis_salti.habilitations" {{ ($data['documents_remis_salti']['habilitations'] ?? false) ? 'checked' : '' }} class="mt-0.5">
                    <span class="text-sm">Habilitations</span>
                </label>
            </div>
        </div>

        {{-- Habilitations / CACES de vos salariés --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="font-semibold mb-2">Autorisations de conduite & habilitations de vos salariés</h2>
            <p class="text-sm text-gray-600 mb-3">Renseignez ci-dessous les salariés qui interviendront sur le site SALTI avec leurs habilitations valides.</p>
            <div class="overflow-x-auto">
                <table class="w-full border border-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Salarié (Nom Prénom)</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Habilitation / CACES</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Date validité</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200" id="hab-table">
                        @php
                            // 3 lignes minimum, plus si déjà saisies
                            $habs = $pdp->intervenants()->whereNotNull('habilitation')->orderBy('id')->get();
                            $habCount = max(3, $habs->count() + 1);
                        @endphp
                        @for($i = 0; $i < $habCount; $i++)
                            @php $h = $habs->get($i); @endphp
                            <tr>
                                <td class="px-2 py-2"><input type="text" data-hab-row="{{ $i }}" data-hab-field="nom_prenom" value="{{ $h->nom_prenom ?? '' }}" placeholder="Nom Prénom" class="w-full border-0 text-sm focus:ring-0"></td>
                                <td class="px-2 py-2"><input type="text" data-hab-row="{{ $i }}" data-hab-field="habilitation" value="{{ $h->habilitation ?? '' }}" placeholder="ex. CACES R489 cat 3" class="w-full border-0 text-sm focus:ring-0"></td>
                                <td class="px-2 py-2"><input type="date" data-hab-row="{{ $i }}" data-hab-field="habilitation_validity" value="{{ $h?->habilitation_validity?->format('Y-m-d') ?? '' }}" class="w-full border-0 text-sm focus:ring-0"></td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-gray-500 mt-2">⚠ Les habilitations doivent être valides à la date de début de l'intervention.</p>
        </div>

        {{-- Autres risques que vous identifiez --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="font-semibold mb-2">Autres risques identifiés</h2>
            <p class="text-sm text-gray-600 mb-3">Ajoutez les risques spécifiques à votre intervention qui ne sont pas dans la liste standard SALTI.</p>
            <div class="overflow-x-auto">
                <table class="w-full border border-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Situation</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Risque</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Mesure préventive</th>
                            <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 uppercase">EU</th>
                            <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 uppercase">EE</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @php
                            $autresRisques = $data['autres_risques'] ?? [];
                            while (count($autresRisques) < 5) $autresRisques[] = [];
                        @endphp
                        @foreach($autresRisques as $i => $ar)
                            <tr>
                                <td class="px-2 py-2"><input type="text" data-ar-row="{{ $i }}" data-ar-field="situation" value="{{ $ar['situation'] ?? '' }}" placeholder="Situation" class="w-full border-0 text-sm focus:ring-0"></td>
                                <td class="px-2 py-2"><input type="text" data-ar-row="{{ $i }}" data-ar-field="risque" value="{{ $ar['risque'] ?? '' }}" placeholder="Risque" class="w-full border-0 text-sm focus:ring-0"></td>
                                <td class="px-2 py-2"><input type="text" data-ar-row="{{ $i }}" data-ar-field="mesure" value="{{ $ar['mesure'] ?? '' }}" placeholder="Mesure" class="w-full border-0 text-sm focus:ring-0"></td>
                                <td class="px-3 py-2 text-center"><input type="checkbox" data-ar-row="{{ $i }}" data-ar-field="eu" {{ ($ar['eu'] ?? false) ? 'checked' : '' }}></td>
                                <td class="px-3 py-2 text-center"><input type="checkbox" data-ar-row="{{ $i }}" data-ar-field="ee" {{ ($ar['ee'] ?? false) ? 'checked' : '' }}></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
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

    // Auto-save : déclenche sur tout input/change des éléments balisés
    let saveTimer;
    document.querySelectorAll('[data-path], [data-cb-path], .ee-radio, [data-hab-row], [data-ar-row]').forEach(el => {
        ['input', 'change'].forEach(evt => el.addEventListener(evt, () => {
            clearTimeout(saveTimer);
            saveTimer = setTimeout(autoSave, 800);
        }));
    });

    function setDeep(obj, path, value) {
        const parts = path.split('.');
        let cur = obj;
        for (let i = 0; i < parts.length - 1; i++) {
            cur[parts[i]] = cur[parts[i]] || {};
            cur = cur[parts[i]];
        }
        cur[parts[parts.length - 1]] = value;
    }

    async function autoSave() {
        const data = {};

        // 1. Champs texte/email/tel : data-path → data[path] = value
        document.querySelectorAll('[data-path]').forEach(el => {
            setDeep(data, el.dataset.path, el.value);
        });

        // 2. Radios .ee-radio (oui/non sous-traitance)
        document.querySelectorAll('.ee-radio:checked').forEach(el => {
            setDeep(data, el.name, el.value);
        });

        // 3. Checkboxes des documents EE → data-cb-path
        document.querySelectorAll('[data-cb-path]').forEach(el => {
            setDeep(data, el.dataset.cbPath, el.checked);
        });

        // 4. Tableau Habilitations (lignes salariés)
        const habs = {};
        document.querySelectorAll('[data-hab-row]').forEach(el => {
            const i = el.dataset.habRow;
            const f = el.dataset.habField;
            habs[i] = habs[i] || {};
            habs[i][f] = el.value;
        });
        // On envoie sous data.intervenants (le contrôleur côté serveur le mappera)
        data.intervenants = Object.values(habs).filter(h => h.nom_prenom || h.habilitation);

        // 5. Tableau "Autres risques"
        const autres = {};
        document.querySelectorAll('[data-ar-row]').forEach(el => {
            const i = el.dataset.arRow;
            const f = el.dataset.arField;
            autres[i] = autres[i] || {};
            autres[i][f] = el.type === 'checkbox' ? el.checked : el.value;
        });
        data.autres_risques = Object.values(autres);

        try {
            const r = await fetch(`/p/${TOKEN}/save`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify({ data })
            });
            const d = await r.json();
            const root = document.querySelector('[x-data]').__x?.$data;
            if (root && d.saved_at) root.lastSaved = `Enregistré à ${d.saved_at}`;
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

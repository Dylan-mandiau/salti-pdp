<x-layouts.pdp title="Plan de prévention - Prestataire">
@php
    $data = $pdp->data;
    // Le prestataire peut éditer tant qu'il n'a pas signé personnellement
    // (même après 'soumission' — pour corriger des erreurs)
    $isLocked = $pdp->signed_by_prestataire_at || in_array($pdp->status, ['signed', 'archived', 'cancelled']);
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
            ✓ Vous avez soumis votre partie à SALTI. Vous pouvez encore modifier vos saisies en cas d'erreur — n'oubliez pas alors de cliquer à nouveau sur <strong>"Soumettre à SALTI"</strong>.
        </div>
    @elseif($pdp->status === 'corrections_requested')
        <div class="bg-orange-50 border border-orange-200 text-orange-800 p-4 rounded mb-6">
            ⚠ SALTI a demandé des corrections. Merci de modifier votre saisie puis de re-soumettre.
        </div>
    @elseif($pdp->status === 'awaiting_signatures' && ! $pdp->signed_by_prestataire_at)
        <div class="bg-purple-50 border border-purple-200 text-purple-800 p-4 rounded mb-6">
            ✓ SALTI a validé votre partie — vous pouvez maintenant <strong>signer</strong> le PDP en bas de page. En cas d'erreur, vous pouvez encore modifier avant signature.
        </div>
    @endif

    {{-- Récap partie SALTI (lecture seule) --}}
    <div class="bg-gray-50 rounded-lg border border-gray-200 p-4 mb-6">
        <h2 class="font-semibold mb-3 text-sm text-gray-700">📋 Informations renseignées par SALTI</h2>
        @php
            $dureeStr = '';
            if (!empty($data['operation']['duree_value']) && !empty($data['operation']['duree_unit'])) {
                $dureeStr = trim($data['operation']['duree_value'].' '.$data['operation']['duree_unit']);
            } else {
                $dureeStr = $data['operation']['duree'] ?? '—';
            }
            $plagesStr = '';
            if (!empty($data['operation']['plage_debut']) && !empty($data['operation']['plage_fin'])) {
                $plagesStr = $data['operation']['plage_debut'].' - '.$data['operation']['plage_fin'];
            } else {
                $plagesStr = $data['operation']['plages_horaires'] ?? '—';
            }
            $nbSalaries = (int)($data['operation']['nb_salaries'] ?? 0);
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
            <div><span class="text-gray-500">Agence :</span> {{ $data['eu']['agence'] ?? '—' }}</div>
            <div><span class="text-gray-500">Donneur d'ordre :</span> {{ $pdp->donneur_ordre_nom }}</div>
            <div><span class="text-gray-500">Opération :</span> {{ $data['operation']['designation'] ?? '—' }}</div>
            <div><span class="text-gray-500">Lieu :</span> {{ $data['operation']['lieu'] ?? '—' }}</div>
            <div><span class="text-gray-500">Date début :</span> {{ $data['operation']['date_debut'] ?? '—' }}</div>
            <div><span class="text-gray-500">Durée :</span> {{ $dureeStr }}</div>
            <div><span class="text-gray-500">Plages horaires :</span> {{ $plagesStr }}</div>
            <div>
                <span class="text-gray-500">Nombre de salariés affectés :</span>
                <strong>{{ $nbSalaries > 0 ? $nbSalaries : '—' }}</strong>
                @if($nbSalaries > 0)
                    <span class="text-xs text-blue-700 ml-1">→ remplissez les habilitations pour ces {{ $nbSalaries }} salarié{{ $nbSalaries > 1 ? 's' : '' }}</span>
                @endif
            </div>
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
                    <label class="block text-sm font-medium text-gray-700 mb-1">SIRET <span class="text-xs text-gray-500">(14 chiffres)</span></label>
                    <input type="text" data-path="ee.siret" value="{{ $data['ee']['siret'] ?? '' }}"
                           {{ $isLocked ? 'disabled' : '' }}
                           maxlength="14" pattern="[0-9]{14}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-mono">
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

            <p class="text-sm text-gray-600 mb-3">1️⃣ Cochez les types de documents fournis :</p>
            <div class="space-y-2 mb-5">
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

            <p class="text-sm text-gray-600 mb-3">2️⃣ Joignez vos fichiers (glisser-déposer ou cliquer) :</p>
            {{-- Zone drag & drop --}}
            <div id="dropzone"
                 class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer hover:border-salti-yellow hover:bg-salti-yellow/5 transition">
                <div class="text-4xl mb-2">📂</div>
                <div class="text-sm font-medium text-gray-700 mb-1">
                    <span class="text-salti-yellow-dark underline">Cliquer pour choisir</span>
                    ou glissez vos fichiers ici
                </div>
                <div class="text-xs text-gray-500">PDF, JPG, PNG, DOCX — max 10 Mo par fichier</div>
                <input type="file" id="dropzone-input" multiple class="hidden" accept=".pdf,.jpg,.jpeg,.png,.docx,.doc">
            </div>

            {{-- Liste des fichiers déjà uploadés --}}
            @php $existingDocs = $pdp->documents()->where('uploaded_by', 'prestataire')->get(); @endphp
            <div id="files-list" class="mt-4 space-y-2">
                @foreach($existingDocs as $doc)
                    <div data-doc-id="{{ $doc->id }}" class="flex items-center justify-between gap-3 p-3 bg-gray-50 border border-gray-200 rounded">
                        <div class="flex items-center gap-2 min-w-0 flex-1">
                            <span class="text-xl shrink-0">📄</span>
                            <div class="min-w-0">
                                <div class="text-sm font-medium truncate">{{ $doc->original_filename }}</div>
                                <div class="text-xs text-gray-500">{{ number_format($doc->size / 1024, 0) }} Ko · {{ $doc->type }}</div>
                            </div>
                        </div>
                        <div class="flex gap-2 shrink-0">
                            <a href="{{ route('prestataire.download-document', ['token' => $token, 'doc' => $doc->id]) }}" class="text-sm text-blue-600 hover:underline">Voir</a>
                            <button type="button" onclick="deleteDoc({{ $doc->id }})" class="text-sm text-red-600 hover:underline">Supprimer</button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Habilitations / CACES de vos salariés --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="font-semibold mb-2">Autorisations de conduite & habilitations de vos salariés</h2>
            @php
                $habs = $pdp->intervenants()->orderBy('id')->get();
                // Nombre de lignes = max(nb_salaries fourni par SALTI, lignes déjà saisies)
                // Au moins 1 ligne pour démarrer
                $habCount = max(1, $nbSalaries, $habs->count());
            @endphp
            <p class="text-sm text-gray-600 mb-3">
                Renseignez ci-dessous les salariés qui interviendront sur le site SALTI avec leurs habilitations valides.
                @if($nbSalaries > 0)
                    <br><span class="text-blue-700">SALTI a indiqué <strong>{{ $nbSalaries }} salarié{{ $nbSalaries > 1 ? 's' : '' }} affecté{{ $nbSalaries > 1 ? 's' : '' }}</strong> — vous trouverez {{ $nbSalaries }} ligne{{ $nbSalaries > 1 ? 's' : '' }} prête{{ $nbSalaries > 1 ? 's' : '' }} à remplir.</span>
                @endif
            </p>
            {{-- Cards : affichage uniforme (1 col mobile, lignes horizontales desktop) --}}
            <div class="space-y-3" id="hab-table">
                @for($i = 0; $i < $habCount; $i++)
                    @php $h = $habs->get($i); @endphp
                    <div data-hab-line class="border border-gray-200 rounded-lg p-3">
                        <div class="flex items-center justify-between mb-2 md:mb-0 md:hidden">
                            <div class="text-xs font-semibold text-gray-500">Salarié #{{ $i + 1 }}</div>
                            <button type="button" onclick="removeHabLine(this)" class="text-red-500 hover:text-red-700 text-2xl leading-none" title="Supprimer">×</button>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-[1fr_1fr_180px_40px] gap-2 md:items-center">
                            <input type="text" data-hab-row="{{ $i }}" data-hab-field="nom_prenom" value="{{ $h->nom_prenom ?? '' }}" placeholder="Nom Prénom" class="border border-gray-200 rounded px-2 py-1.5 text-sm">
                            <input type="text" data-hab-row="{{ $i }}" data-hab-field="habilitation" value="{{ $h->habilitation ?? '' }}" placeholder="ex. CACES R489 cat 3" class="border border-gray-200 rounded px-2 py-1.5 text-sm">
                            <div>
                                <label class="text-xs text-gray-500 md:hidden">Date de validité</label>
                                <input type="date" data-hab-row="{{ $i }}" data-hab-field="habilitation_validity" value="{{ $h?->habilitation_validity?->format('Y-m-d') ?? '' }}" class="w-full border border-gray-200 rounded px-2 py-1.5 text-sm">
                            </div>
                            <button type="button" onclick="removeHabLine(this)" class="hidden md:block text-red-500 hover:text-red-700 text-lg justify-self-center" title="Supprimer">×</button>
                        </div>
                    </div>
                @endfor
            </div>
            <button type="button" onclick="addHabLine()"
                    class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 border border-gray-300 rounded text-sm hover:bg-gray-50 hover:border-salti-yellow transition">
                <span class="text-lg">+</span> Ajouter un salarié
            </button>
            <p class="text-xs text-gray-500 mt-2">⚠ Les habilitations doivent être valides à la date de début de l'intervention.</p>
        </div>

        {{-- Attestation de prise de connaissance — chaque salarié signe individuellement --}}
        @if($habs->count() > 0)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="font-semibold mb-2">Attestation de prise de connaissance du Plan de Prévention</h2>
            <p class="text-sm text-gray-600 mb-4">
                Chaque salarié intervenant doit signer ci-dessous pour attester avoir pris connaissance
                du présent plan de prévention, des risques et des mesures associées.
            </p>

            <div class="space-y-4">
                @foreach($habs as $iv)
                    <div class="border border-gray-200 rounded-lg p-4" data-intervenant-id="{{ $iv->id }}">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <div class="font-medium">{{ $iv->nom_prenom }}</div>
                                @if($iv->habilitation)
                                    <div class="text-xs text-gray-500">{{ $iv->habilitation }}</div>
                                @endif
                            </div>
                            @if($iv->signature_data)
                                <span class="text-sm text-green-700 bg-green-50 px-3 py-1 rounded border border-green-200">
                                    ✓ Signé le {{ $iv->date_signature?->format('d/m/Y') }}
                                </span>
                            @endif
                        </div>

                        @if(! $iv->signature_data)
                            <canvas id="sig-iv-{{ $iv->id }}" class="border-2 border-dashed border-gray-300 rounded w-full bg-white" height="120"></canvas>
                            <div class="flex gap-2 mt-2">
                                <button type="button" onclick="clearIntervenantSig({{ $iv->id }})" class="text-sm text-gray-600">Effacer</button>
                                <button type="button" onclick="saveIntervenantSig({{ $iv->id }})"
                                        class="ml-auto bg-salti-yellow hover:bg-salti-yellow-dark text-black font-semibold px-4 py-2 rounded text-sm">
                                    Signer l'attestation
                                </button>
                            </div>
                        @else
                            <div class="flex justify-center mt-2 p-3 bg-gray-50 rounded border border-gray-200">
                                <img src="{{ $iv->signature_data }}" alt="Signature" class="max-h-20">
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            <p class="text-xs text-gray-500 mt-3">
                💡 Sur tablette / smartphone, le salarié peut signer directement avec son doigt ou un stylet.
            </p>
        </div>
        @endif

        {{-- Liste des risques standards SALTI déjà identifiés (lecture seule) --}}
        @php
            $risquesStandard = [
                'arrivee_site' => 'Arrivée sur le site',
                'circulation_interne' => 'Circulation interne (véhicule, engin, à pied)',
                'stationnement' => 'Stationnement, entreposage',
                'sols_souilles' => 'Sols souillés (produits, liquides, outils)',
                'travail_hauteur' => 'Travail en hauteur — Utilisation d\'une nacelle',
                'levage_manutention' => 'Levage et manutention',
                'soudure_decoupe' => 'Soudure / Découpe de matériaux',
                'dechets' => 'Déchets produits par l\'activité',
                'electrique' => 'Intervention sur installations électriques',
                'produits_chimiques' => 'Utilisation de produits chimiques dangereux',
                'flexibles_engins' => 'Intervention sur flexibles d\'engins',
                'multi_interventions' => 'Multi interventions',
                'contamination' => 'Contamination (exposition, grippe, virus…)',
            ];
            $risquesData = $data['risques'] ?? [];
        @endphp
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
            <h2 class="font-semibold mb-2 text-blue-900">📋 Risques standards SALTI déjà couverts</h2>
            <p class="text-sm text-blue-800 mb-4">
                Voici les risques d'interférence prévus dans le PDP standard SALTI. Vous n'avez <strong>pas besoin</strong> de les redéclarer ci-dessous — ils sont gérés par SALTI dans le wizard.
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                @foreach($risquesStandard as $key => $label)
                    @php $applicable = $risquesData[$key]['applicable'] ?? false; @endphp
                    <div class="flex items-start gap-2 text-sm bg-white p-2 rounded border {{ $applicable ? 'border-salti-yellow' : 'border-gray-200' }}">
                        <span class="mt-0.5">{{ $applicable ? '☒' : '☐' }}</span>
                        <span class="{{ $applicable ? 'font-semibold' : 'text-gray-600' }}">
                            {{ $label }}
                            @if($applicable)
                                <span class="text-xs text-salti-yellow-dark ml-1">— applicable à votre intervention</span>
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>
            <p class="text-xs text-blue-700 mt-4">
                💡 <strong>Si un risque ci-dessus n'est pas coché par SALTI alors qu'il devrait l'être</strong>, signalez-le par email à votre interlocuteur SALTI ou utilisez la section ci-dessous pour le mentionner.
            </p>
        </div>

        {{-- Autres risques que vous identifiez --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="font-semibold mb-2">Autres risques identifiés</h2>
            <p class="text-sm text-gray-600 mb-3">
                Si votre intervention présente des risques spécifiques <strong>non couverts par la liste ci-dessus</strong>, ajoutez-les ici. Sinon, laissez vide.
            </p>
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
            @if(in_array($pdp->status, ['awaiting_prestataire', 'corrections_requested', 'awaiting_validation']))
                <form method="POST" action="{{ route('prestataire.submit', $token) }}"
                      onsubmit="return confirm('Soumettre votre partie à SALTI pour validation ?');"
                      class="flex justify-end">
                    @csrf
                    <button type="submit"
                            class="bg-salti-yellow hover:bg-salti-yellow-dark text-black font-semibold px-5 py-2.5 rounded">
                        @if($pdp->status === 'awaiting_validation')
                            🔄 Re-soumettre à SALTI
                        @else
                            Soumettre à SALTI →
                        @endif
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
    function attachAutoSave(el) {
        ['input', 'change'].forEach(evt => el.addEventListener(evt, () => {
            clearTimeout(saveTimer);
            saveTimer = setTimeout(autoSave, 800);
        }));
    }
    document.querySelectorAll('[data-path], [data-cb-path], .ee-radio, [data-hab-row], [data-ar-row]').forEach(attachAutoSave);

    // Ajout / suppression de lignes Habilitations dynamiquement (format cards responsive)
    function buildHabCardHtml(idx) {
        return `
            <div class="flex items-center justify-between mb-2 md:mb-0 md:hidden">
                <div class="text-xs font-semibold text-gray-500">Salarié #${idx + 1}</div>
                <button type="button" onclick="removeHabLine(this)" class="text-red-500 hover:text-red-700 text-2xl leading-none">×</button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-[1fr_1fr_180px_40px] gap-2 md:items-center">
                <input type="text" data-hab-row="${idx}" data-hab-field="nom_prenom" placeholder="Nom Prénom" class="border border-gray-200 rounded px-2 py-1.5 text-sm">
                <input type="text" data-hab-row="${idx}" data-hab-field="habilitation" placeholder="ex. CACES R489 cat 3" class="border border-gray-200 rounded px-2 py-1.5 text-sm">
                <div>
                    <label class="text-xs text-gray-500 md:hidden">Date de validité</label>
                    <input type="date" data-hab-row="${idx}" data-hab-field="habilitation_validity" class="w-full border border-gray-200 rounded px-2 py-1.5 text-sm">
                </div>
                <button type="button" onclick="removeHabLine(this)" class="hidden md:block text-red-500 hover:text-red-700 text-lg justify-self-center">×</button>
            </div>
        `;
    }

    window.addHabLine = function() {
        const container = document.getElementById('hab-table');
        const idx = container.querySelectorAll('[data-hab-line]').length;
        const card = document.createElement('div');
        card.setAttribute('data-hab-line', '');
        card.className = 'border border-gray-200 rounded-lg p-3';
        card.innerHTML = buildHabCardHtml(idx);
        container.appendChild(card);
        card.querySelectorAll('input').forEach(attachAutoSave);
        card.querySelector('input').focus();
    };

    window.removeHabLine = function(btn) {
        const container = document.getElementById('hab-table');
        const card = btn.closest('[data-hab-line]');
        if (container.querySelectorAll('[data-hab-line]').length <= 1) {
            // Garde au moins une ligne — vide les champs
            card.querySelectorAll('input').forEach(i => i.value = '');
        } else {
            card.remove();
        }
        // Re-numérote les data-hab-row pour rester cohérent
        container.querySelectorAll('[data-hab-line]').forEach((el, i) => {
            el.querySelectorAll('[data-hab-row]').forEach(input => input.setAttribute('data-hab-row', i));
        });
        clearTimeout(saveTimer);
        saveTimer = setTimeout(autoSave, 200);
    };

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

    // Drag & drop upload
    const dropzone = document.getElementById('dropzone');
    const dropzoneInput = document.getElementById('dropzone-input');
    const filesList = document.getElementById('files-list');

    if (dropzone) {
        dropzone.addEventListener('click', () => dropzoneInput.click());
        dropzone.addEventListener('dragover', (e) => { e.preventDefault(); dropzone.classList.add('border-salti-yellow', 'bg-salti-yellow/10'); });
        dropzone.addEventListener('dragleave', () => dropzone.classList.remove('border-salti-yellow', 'bg-salti-yellow/10'));
        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('border-salti-yellow', 'bg-salti-yellow/10');
            for (const file of e.dataTransfer.files) {
                uploadFile(file);
            }
        });
        dropzoneInput.addEventListener('change', () => {
            for (const file of dropzoneInput.files) uploadFile(file);
            dropzoneInput.value = '';
        });
    }

    async function uploadFile(file) {
        if (file.size > 10 * 1024 * 1024) {
            alert(`Le fichier "${file.name}" dépasse 10 Mo`);
            return;
        }
        const fd = new FormData();
        fd.append('file', file);
        fd.append('type', 'autre');
        fd.append('label', file.name);

        // Petit indicateur visuel pendant l'upload
        const tempLine = document.createElement('div');
        tempLine.className = 'flex items-center gap-2 p-3 bg-blue-50 border border-blue-200 rounded text-sm';
        tempLine.innerHTML = `<span>⏳</span><span>Upload en cours : ${file.name}</span>`;
        filesList.appendChild(tempLine);

        try {
            const r = await fetch(`/p/${TOKEN}/upload`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: fd,
            });
            const d = await r.json();
            tempLine.remove();
            if (d.error) { alert(d.error); return; }

            // Affiche la nouvelle ligne
            const line = document.createElement('div');
            line.setAttribute('data-doc-id', d.id);
            line.className = 'flex items-center justify-between gap-3 p-3 bg-gray-50 border border-gray-200 rounded';
            const safeName = document.createElement('span');
            safeName.textContent = d.filename;
            line.innerHTML = `
                <div class="flex items-center gap-2 min-w-0 flex-1">
                    <span class="text-xl shrink-0">📄</span>
                    <div class="min-w-0">
                        <div class="text-sm font-medium truncate" data-name></div>
                        <div class="text-xs text-gray-500">${(d.size / 1024).toFixed(0)} Ko · ${d.type}</div>
                    </div>
                </div>
                <div class="flex gap-2 shrink-0">
                    <a href="${d.download_url}" class="text-sm text-blue-600 hover:underline">Voir</a>
                    <button type="button" data-delete-btn class="text-sm text-red-600 hover:underline">Supprimer</button>
                </div>`;
            line.querySelector('[data-name]').textContent = d.filename;
            line.querySelector('[data-delete-btn]').addEventListener('click', () => deleteDoc(d.id));
            filesList.appendChild(line);
        } catch (err) {
            tempLine.remove();
            alert('Erreur lors de l\'upload : '+err.message);
        }
    }

    window.deleteDoc = async function(docId) {
        if (! confirm('Supprimer ce fichier ?')) return;
        const r = await fetch(`/p/${TOKEN}/upload/${docId}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        if (r.ok) {
            document.querySelector(`[data-doc-id="${docId}"]`)?.remove();
        }
    };

    // Signatures d'attestation par intervenant
    const intervenantSigPads = {};
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('canvas[id^="sig-iv-"]').forEach(canvas => {
            const id = canvas.id.replace('sig-iv-', '');
            const ratio = window.devicePixelRatio || 1;
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext('2d').scale(ratio, ratio);
            intervenantSigPads[id] = new SignaturePad(canvas, { penColor: '#000' });
        });
    });
    window.clearIntervenantSig = function(id) { intervenantSigPads[id]?.clear(); };
    window.saveIntervenantSig = async function(id) {
        const pad = intervenantSigPads[id];
        if (!pad || pad.isEmpty()) {
            alert('Veuillez signer avant de valider.');
            return;
        }
        const dataUrl = pad.toDataURL('image/png');
        try {
            const r = await fetch(`/p/${TOKEN}/sign-intervenant/${id}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify({ signature_data: dataUrl }),
            });
            const d = await r.json();
            if (d.ok) {
                location.reload(); // recharger pour afficher la signature persistée
            } else {
                alert('Erreur : '+(d.error || 'inconnue'));
            }
        } catch (err) {
            alert('Erreur lors de la sauvegarde : '+err.message);
        }
    }
</script>
</x-layouts.pdp>

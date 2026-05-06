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
        @php
            $existingDocs = $pdp->documents()->where('uploaded_by', 'prestataire')->get();
            $docsByType = $existingDocs->groupBy('type');
            // Catégories demandées par SALTI (selon la check-list cochée)
            $requestedTypes = [
                'autorisation_conduite' => ['label' => 'Autorisation de conduite', 'icon' => '🚜'],
                'caces' => ['label' => 'CACES', 'icon' => '📜'],
                'habilitation' => ['label' => 'Habilitations', 'icon' => '⚡'],
            ];
            $reqMap = [
                'autorisation_conduite' => $data['documents_remis_salti']['autorisation_conduite'] ?? false,
                'caces' => $data['documents_remis_salti']['caces'] ?? false,
                'habilitation' => $data['documents_remis_salti']['habilitations'] ?? false,
            ];
        @endphp
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="font-semibold mb-1">Documents que vous remettez à SALTI</h2>
            <p class="text-sm text-gray-600 mb-4">
                Cochez les types fournis et joignez les fichiers correspondants. Vous pouvez aussi ajouter des documents libres.
            </p>

            {{-- Section 1 : Documents demandés par SALTI --}}
            <div class="mb-6">
                <div class="flex items-center gap-2 mb-3">
                    <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2 py-0.5 rounded">📌 Demandés par SALTI</span>
                </div>
                <div class="space-y-3">
                    @foreach($requestedTypes as $typeKey => $meta)
                        @php
                            $cbPath = $typeKey === 'habilitation' ? 'documents_remis_salti.habilitations' : 'documents_remis_salti.'.$typeKey;
                            $isRequested = $reqMap[$typeKey];
                            $typeDocs = $docsByType[$typeKey] ?? collect();
                        @endphp
                        <div class="border border-gray-200 rounded-lg p-3" data-type-block="{{ $typeKey }}">
                            <label class="flex items-center gap-2 cursor-pointer mb-2">
                                <input type="checkbox" data-cb-path="{{ $cbPath }}" {{ $isRequested ? 'checked' : '' }}>
                                <span class="text-sm font-medium">{{ $meta['icon'] }} {{ $meta['label'] }}</span>
                                @if($typeDocs->count() > 0)
                                    <span class="bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded">{{ $typeDocs->count() }} fichier{{ $typeDocs->count() > 1 ? 's' : '' }}</span>
                                @endif
                            </label>

                            <div class="ml-6">
                                <div class="dz-mini border border-dashed border-gray-300 rounded p-3 text-center cursor-pointer hover:border-salti-yellow hover:bg-salti-yellow/5 transition text-xs"
                                     data-dropzone="{{ $typeKey }}">
                                    <span class="text-gray-600">📂 <span class="text-salti-yellow-dark underline">Joindre un fichier</span> pour {{ strtolower($meta['label']) }}</span>
                                    <input type="file" class="hidden" data-dz-input="{{ $typeKey }}" multiple accept=".pdf,.jpg,.jpeg,.png,.docx,.doc">
                                </div>
                                <div class="mt-2 space-y-2" data-files-list="{{ $typeKey }}">
                                    @foreach($typeDocs as $doc)
                                        <div data-doc-id="{{ $doc->id }}" class="flex items-center justify-between gap-3 p-2 bg-gray-50 border border-gray-200 rounded">
                                            <div class="flex items-center gap-2 min-w-0 flex-1">
                                                <span class="text-lg shrink-0">📄</span>
                                                <div class="min-w-0">
                                                    <div class="text-xs font-medium truncate">{{ $doc->original_filename }}</div>
                                                    <div class="text-xs text-gray-500">{{ number_format($doc->size / 1024, 0) }} Ko</div>
                                                </div>
                                            </div>
                                            <div class="flex gap-2 shrink-0">
                                                <a href="{{ route('prestataire.download-document', ['token' => $token, 'doc' => $doc->id]) }}" class="text-xs text-blue-600 hover:underline">Voir</a>
                                                <button type="button" onclick="deleteDoc({{ $doc->id }})" class="text-xs text-red-600 hover:underline">Supprimer</button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Section 2 : Autres documents (uploads libres) --}}
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <span class="bg-gray-100 text-gray-700 text-xs font-semibold px-2 py-0.5 rounded">➕ Autres documents (libre)</span>
                    @php $autreDocs = $docsByType['autre'] ?? collect(); @endphp
                    @if($autreDocs->count() > 0)
                        <span class="bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded">{{ $autreDocs->count() }} fichier{{ $autreDocs->count() > 1 ? 's' : '' }}</span>
                    @endif
                </div>
                <p class="text-xs text-gray-500 mb-2">Permis feu papier scanné, FDS, photos d'EPI, etc.</p>
                <div id="dropzone"
                     class="border-2 border-dashed border-gray-300 rounded-lg p-5 text-center cursor-pointer hover:border-salti-yellow hover:bg-salti-yellow/5 transition"
                     data-dropzone="autre">
                    <div class="text-3xl mb-2">📂</div>
                    <div class="text-sm font-medium text-gray-700 mb-1">
                        <span class="text-salti-yellow-dark underline">Cliquer pour choisir</span>
                        ou glissez vos fichiers ici
                    </div>
                    <div class="text-xs text-gray-500">PDF, JPG, PNG, DOCX — max 10 Mo par fichier</div>
                    <input type="file" id="dropzone-input" data-dz-input="autre" multiple class="hidden" accept=".pdf,.jpg,.jpeg,.png,.docx,.doc">
                </div>
                <div id="files-list" class="mt-3 space-y-2" data-files-list="autre">
                    @foreach($autreDocs as $doc)
                        <div data-doc-id="{{ $doc->id }}" class="flex items-center justify-between gap-3 p-3 bg-gray-50 border border-gray-200 rounded">
                            <div class="flex items-center gap-2 min-w-0 flex-1">
                                <span class="text-xl shrink-0">📄</span>
                                <div class="min-w-0">
                                    <div class="text-sm font-medium truncate">{{ $doc->original_filename }}</div>
                                    <div class="text-xs text-gray-500">{{ number_format($doc->size / 1024, 0) }} Ko</div>
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

        {{-- Permis feu — apparaît seulement si la case est cochée par SALTI --}}
        @if($data['documents_remis_ee']['permis_feu'] ?? false)
            @php $pf = $data['permis_feu'] ?? []; @endphp
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6" x-data="{ mode: '{{ $pf['mode_remplissage'] ?? 'paper' }}' }">
                <h2 class="font-semibold mb-2">🔥 Permis feu</h2>
                <p class="text-sm text-gray-600 mb-3">Ce document est requis pour les travaux par points chauds (soudure, meulage, découpe, etc.).</p>

                <div class="bg-yellow-50 border border-yellow-200 rounded p-3 mb-4">
                    <p class="text-sm font-medium mb-2">Comment souhaitez-vous compléter le Permis feu ?</p>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <label class="flex items-start gap-2 cursor-pointer flex-1 p-3 border rounded {{ ($pf['mode_remplissage'] ?? '') === 'paper' ? 'border-salti-yellow bg-white' : 'border-gray-200' }}">
                            <input type="radio" data-cb-path="permis_feu.mode_remplissage" name="pf_mode" value="paper" x-model="mode" class="mt-0.5">
                            <span class="text-sm">
                                <strong>📄 Papier</strong><br>
                                Je télécharge le PDF pré-rempli, je l'imprime, je le complète à la main, le fais signer, scan + upload via la zone "Documents".
                            </span>
                        </label>
                        <label class="flex items-start gap-2 cursor-pointer flex-1 p-3 border rounded {{ ($pf['mode_remplissage'] ?? '') === 'online' ? 'border-salti-yellow bg-white' : 'border-gray-200' }}">
                            <input type="radio" data-cb-path="permis_feu.mode_remplissage" name="pf_mode" value="online" x-model="mode" class="mt-0.5">
                            <span class="text-sm">
                                <strong>💻 En ligne</strong><br>
                                Je remplis directement les champs ci-dessous, le PDF se génère automatiquement.
                            </span>
                        </label>
                    </div>
                </div>

                {{-- Mode "papier" : lien de téléchargement --}}
                <div x-show="mode === 'paper'" class="bg-blue-50 border border-blue-200 rounded p-3 mb-3 text-sm">
                    <p class="font-medium mb-1">📥 Téléchargez le PDF Permis feu pré-rempli :</p>
                    <a href="{{ route('prestataire.download-permis-feu', $token) }}" class="inline-block mt-2 bg-salti-yellow hover:bg-salti-yellow-dark text-black font-semibold px-4 py-2 rounded">
                        📄 Télécharger le Permis feu pré-rempli
                    </a>
                    <p class="text-xs text-gray-600 mt-2">Une fois rempli + signé, scannez ou photographiez le document et uploadez-le dans la section "Documents que vous remettez à SALTI" plus haut.</p>
                </div>

                {{-- Mode "en ligne" : formulaire complet --}}
                <div x-show="mode === 'online'" x-cloak class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium mb-1">Mode opératoire (référence)</label>
                        <input type="text" data-path="permis_feu.mode_operatoire" value="{{ $pf['mode_operatoire'] ?? '' }}" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Liste des opérateurs autorisés</label>
                        <textarea data-path="permis_feu.operateurs_autorises" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" rows="3" placeholder="Auto-rempli depuis vos salariés intervenants si laissé vide">{{ $pf['operateurs_autorises'] ?? '' }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="font-medium text-sm mb-2">Type de travaux par points chauds</p>
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" data-cb-path="permis_feu.travaux.soudage" {{ ($pf['travaux']['soudage'] ?? false) ? 'checked' : '' }}> Soudage</label>
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" data-cb-path="permis_feu.travaux.tronconnage" {{ ($pf['travaux']['tronconnage'] ?? false) ? 'checked' : '' }}> Tronçonnage</label>
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" data-cb-path="permis_feu.travaux.decoupage" {{ ($pf['travaux']['decoupage'] ?? false) ? 'checked' : '' }}> Découpage</label>
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" data-cb-path="permis_feu.travaux.meulage" {{ ($pf['travaux']['meulage'] ?? false) ? 'checked' : '' }}> Meulage</label>
                            <input type="text" data-path="permis_feu.travaux.autre" value="{{ $pf['travaux']['autre'] ?? '' }}" placeholder="Autre travail..." class="w-full mt-1 border border-gray-300 rounded px-3 py-1.5 text-sm">
                        </div>
                        <div>
                            <p class="font-medium text-sm mb-2">Matériels utilisés</p>
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" data-cb-path="permis_feu.materiels.poste_souder" {{ ($pf['materiels']['poste_souder'] ?? false) ? 'checked' : '' }}> Poste à souder</label>
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" data-cb-path="permis_feu.materiels.chalumeau" {{ ($pf['materiels']['chalumeau'] ?? false) ? 'checked' : '' }}> Chalumeau</label>
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" data-cb-path="permis_feu.materiels.laser" {{ ($pf['materiels']['laser'] ?? false) ? 'checked' : '' }}> Laser</label>
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" data-cb-path="permis_feu.materiels.tronconneuse" {{ ($pf['materiels']['tronconneuse'] ?? false) ? 'checked' : '' }}> Tronçonneuse</label>
                            <input type="text" data-path="permis_feu.materiels.autre" value="{{ $pf['materiels']['autre'] ?? '' }}" placeholder="Autre matériel..." class="w-full mt-1 border border-gray-300 rounded px-3 py-1.5 text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Risques particuliers (produits, procédés, stockages...)</label>
                        <textarea data-path="permis_feu.risques_particuliers" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" rows="2">{{ $pf['risques_particuliers'] ?? '' }}</textarea>
                    </div>

                    <div class="flex flex-wrap gap-4">
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" data-cb-path="permis_feu.zone_atex_presence" {{ ($pf['zone_atex_presence'] ?? false) ? 'checked' : '' }}> Présence de zones ATEX</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" data-cb-path="permis_feu.zone_atex_proximite" {{ ($pf['zone_atex_proximite'] ?? false) ? 'checked' : '' }}> Proximité de zones ATEX</label>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Détails ATEX (type, étendue, produits...)</label>
                        <textarea data-path="permis_feu.zone_atex_details" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" rows="2">{{ $pf['zone_atex_details'] ?? '' }}</textarea>
                    </div>

                    <p class="font-medium text-sm mt-3">Documents associés</p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-sm">
                        <label class="flex items-center gap-2"><input type="checkbox" data-cb-path="permis_feu.documents_associes.autorisation_travail" {{ ($pf['documents_associes']['autorisation_travail'] ?? false) ? 'checked' : '' }}> Autorisation de travail</label>
                        <label class="flex items-center gap-2"><input type="checkbox" data-cb-path="permis_feu.documents_associes.permis_penetrer" {{ ($pf['documents_associes']['permis_penetrer'] ?? false) ? 'checked' : '' }}> Permis de pénétrer</label>
                        <label class="flex items-center gap-2"><input type="checkbox" data-cb-path="permis_feu.documents_associes.drpce" {{ ($pf['documents_associes']['drpce'] ?? false) ? 'checked' : '' }}> DRPCE</label>
                        <label class="flex items-center gap-2"><input type="checkbox" data-cb-path="permis_feu.documents_associes.certificat_degazage" {{ ($pf['documents_associes']['certificat_degazage'] ?? false) ? 'checked' : '' }}> Cert. dégazage</label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
                        <div>
                            <label class="block text-sm font-medium mb-1">Surveillance pendant les travaux (nom)</label>
                            <input type="text" data-path="permis_feu.surveillance_pendant" value="{{ $pf['surveillance_pendant'] ?? '' }}" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Surveillance après travaux (nom)</label>
                            <input type="text" data-path="permis_feu.surveillance_apres_nom" value="{{ $pf['surveillance_apres_nom'] ?? '' }}" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Emplacement des moyens d'alerte (incendie / accident)</label>
                        <textarea data-path="permis_feu.alerte_emplacement" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" rows="2">{{ $pf['alerte_emplacement'] ?? '' }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium mb-1">Personne à contacter en cas d'accident</label>
                            <input type="text" data-path="permis_feu.contact_accident_nom" value="{{ $pf['contact_accident_nom'] ?? '' }}" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Téléphone</label>
                            <input type="tel" data-path="permis_feu.contact_accident_tel" value="{{ $pf['contact_accident_tel'] ?? '' }}" maxlength="20" class="pdp-tel-input w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        </div>
                    </div>

                    <p class="text-xs text-gray-500 mt-2">💡 Le PDF Permis feu est régénéré automatiquement à chaque modification. Téléchargez-le quand vous avez terminé pour signature.</p>
                    <a href="{{ route('prestataire.download-permis-feu', $token) }}" class="inline-block mt-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded text-sm">
                        📄 Télécharger le Permis feu
                    </a>
                </div>
            </div>
        @endif

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
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6" x-data="{ documentsConsulted: false }">
                    <h2 class="font-semibold mb-3">📝 Votre signature finale</h2>

                    {{-- Bloc obligation de consultation --}}
                    <div class="bg-yellow-50 border border-yellow-300 rounded-lg p-4 mb-4">
                        <p class="text-sm font-medium mb-3">
                            ⚠ <strong>Avant de signer</strong>, vous devez consulter les documents qui vous engagent :
                        </p>
                        <ul class="space-y-2 text-sm mb-3">
                            <li class="flex items-center justify-between gap-3">
                                <span>📋 Le Plan de Prévention SALTI</span>
                                <a href="{{ route('prestataire.download-main-pdp', $token) }}" target="_blank" class="text-blue-600 hover:underline whitespace-nowrap">👁 Consulter</a>
                            </li>
                            @if($data['documents_remis_ee']['plan_acces'] ?? false)
                                @if($pdp->agency?->access_plan_path)
                                <li class="flex items-center justify-between gap-3">
                                    <span>🏢 Plan d'accès / circulation</span>
                                    <a href="{{ route('prestataire.download-plan-acces', $token) }}" target="_blank" class="text-blue-600 hover:underline whitespace-nowrap">👁 Consulter</a>
                                </li>
                                @endif
                            @endif
                            @if($data['documents_remis_ee']['permis_feu'] ?? false)
                                <li class="flex items-center justify-between gap-3">
                                    <span>🔥 Permis feu</span>
                                    <a href="{{ route('prestataire.download-permis-feu', $token) }}" target="_blank" class="text-blue-600 hover:underline whitespace-nowrap">👁 Consulter</a>
                                </li>
                            @endif
                            @if($data['documents_remis_ee']['convention_pret'] ?? false)
                                <li class="flex items-center justify-between gap-3">
                                    <span>📋 Convention de prêt de matériel</span>
                                    <a href="{{ route('prestataire.download-convention-pret', $token) }}" target="_blank" class="text-blue-600 hover:underline whitespace-nowrap">👁 Consulter</a>
                                </li>
                            @endif
                        </ul>
                        <label class="flex items-start gap-2 cursor-pointer p-3 bg-white rounded border border-yellow-300">
                            <input type="checkbox" x-model="documentsConsulted" class="mt-0.5">
                            <span class="text-sm font-medium">
                                J'ai lu et compris l'ensemble des documents ci-dessus, et je m'engage à les respecter.
                            </span>
                        </label>
                    </div>

                    <form method="POST" action="{{ route('prestataire.sign', $token) }}" x-bind:class="documentsConsulted ? '' : 'opacity-50 pointer-events-none'">
                        @csrf
                        <input type="text" name="signature_fonction" placeholder="Votre fonction" required
                               class="w-full border border-gray-300 rounded px-3 py-2 mb-3">
                        <canvas id="sig-ee" class="border-2 border-dashed border-gray-300 rounded w-full bg-white" height="160"></canvas>
                        <input type="hidden" name="signature_data" id="sig-ee-data">
                        <div class="flex gap-2 mt-2">
                            <button type="button" onclick="sigEE.clear()" class="text-sm text-gray-600">Effacer</button>
                            <button type="submit" x-bind:disabled="!documentsConsulted" onclick="return submitSigEE()"
                                    class="ml-auto bg-salti-yellow text-black font-semibold px-4 py-2 rounded disabled:opacity-50 disabled:cursor-not-allowed">
                                Signer
                            </button>
                        </div>
                        <p x-show="!documentsConsulted" class="text-xs text-orange-600 mt-2">⚠ Cochez la case ci-dessus pour activer la signature.</p>
                    </form>
                </div>
            @endif
        @endif

        @if($pdp->signed_by_prestataire_at)
            <div class="bg-green-50 border border-green-200 text-green-800 p-4 rounded mb-6">
                ✓ Vous avez signé le {{ $pdp->signed_by_prestataire_at->format('d/m/Y à H:i') }}
                @if($pdp->status === 'signed')
                    — Le PDP est <strong>entièrement signé</strong> par les deux parties.
                @elseif($pdp->status === 'awaiting_signatures')
                    — En attente de la signature SALTI.
                @endif
            </div>
        @endif

        {{-- Récap documents téléchargeables : visible dès la soumission --}}
        @if(in_array($pdp->status, ['awaiting_validation', 'awaiting_signatures', 'signed', 'archived']) || $pdp->signed_by_prestataire_at)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mt-6">
                <h2 class="font-semibold mb-1">📁 Vos documents</h2>
                <p class="text-sm text-gray-600 mb-4">
                    Conservez ces documents pour vos archives. Vous pouvez y revenir à tout moment via ce lien.
                </p>
                <ul class="divide-y divide-gray-200 border border-gray-200 rounded-lg">
                    {{-- Le PDP : consultation tant que non signé, téléchargement après --}}
                    <li class="flex items-center justify-between gap-3 px-4 py-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="text-2xl flex-shrink-0">📋</span>
                            <div class="min-w-0">
                                <div class="font-medium text-sm truncate">Plan de Prévention</div>
                                <div class="text-xs text-gray-500">
                                    @if($pdp->status === 'signed' || $pdp->status === 'archived')
                                        Signé par les 2 parties — version finale
                                    @elseif($pdp->signed_by_prestataire_at)
                                        Signé de votre côté — en attente SALTI
                                    @else
                                        Téléchargement disponible après votre signature
                                    @endif
                                </div>
                            </div>
                        </div>
                        <a href="{{ route('prestataire.download-main-pdp', $token) }}" target="_blank"
                           class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium px-3 py-1.5 rounded whitespace-nowrap">
                            @if($pdp->signed_by_prestataire_at)
                                📥 Télécharger
                            @else
                                👁 Consulter
                            @endif
                        </a>
                    </li>

                    {{-- Plan d'accès agence --}}
                    @if($pdp->agency?->access_plan_path && ($data['documents_remis_ee']['plan_acces'] ?? false))
                        <li class="flex items-center justify-between gap-3 px-4 py-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="text-2xl flex-shrink-0">🏢</span>
                                <div class="min-w-0">
                                    <div class="font-medium text-sm truncate">Plan d'accès / circulation</div>
                                    <div class="text-xs text-gray-500">Agence {{ $pdp->agency->city ?? $pdp->agency->name }}</div>
                                </div>
                            </div>
                            <a href="{{ route('prestataire.download-plan-acces', $token) }}" target="_blank"
                               class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium px-3 py-1.5 rounded whitespace-nowrap">
                                📥 Télécharger
                            </a>
                        </li>
                    @endif

                    {{-- Permis feu --}}
                    @if($data['documents_remis_ee']['permis_feu'] ?? false)
                        <li class="flex items-center justify-between gap-3 px-4 py-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="text-2xl flex-shrink-0">🔥</span>
                                <div class="min-w-0">
                                    <div class="font-medium text-sm truncate">Permis feu</div>
                                    <div class="text-xs text-gray-500">Pré-rempli — formulaire PR0103-bis</div>
                                </div>
                            </div>
                            <a href="{{ route('prestataire.download-permis-feu', $token) }}" target="_blank"
                               class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium px-3 py-1.5 rounded whitespace-nowrap">
                                📥 Télécharger
                            </a>
                        </li>
                    @endif

                    {{-- Convention de prêt --}}
                    @if($data['documents_remis_ee']['convention_pret'] ?? false)
                        <li class="flex items-center justify-between gap-3 px-4 py-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="text-2xl flex-shrink-0">📋</span>
                                <div class="min-w-0">
                                    <div class="font-medium text-sm truncate">Convention de prêt de matériel</div>
                                    <div class="text-xs text-gray-500">Pré-remplie — formulaire PR0102</div>
                                </div>
                            </div>
                            <a href="{{ route('prestataire.download-convention-pret', $token) }}" target="_blank"
                               class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium px-3 py-1.5 rounded whitespace-nowrap">
                                📥 Télécharger
                            </a>
                        </li>
                    @endif
                </ul>

                @if(! $pdp->signed_by_prestataire_at)
                    <p class="text-xs text-gray-500 mt-3">
                        💡 Vous pourrez aussi imprimer ces documents, les remplir manuellement et les ré-uploader si vous préférez la version papier.
                    </p>
                @endif
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

    // Drag & drop upload : on supporte plusieurs dropzones (type-aware)
    document.querySelectorAll('[data-dropzone]').forEach(dz => {
        const type = dz.getAttribute('data-dropzone');
        const input = dz.querySelector(`[data-dz-input="${type}"]`);
        if (! input) return;

        dz.addEventListener('click', (e) => {
            // Évite que le clic sur le label parent re-trigger
            if (e.target.tagName === 'INPUT') return;
            input.click();
        });
        dz.addEventListener('dragover', (e) => { e.preventDefault(); dz.classList.add('border-salti-yellow', 'bg-salti-yellow/10'); });
        dz.addEventListener('dragleave', () => dz.classList.remove('border-salti-yellow', 'bg-salti-yellow/10'));
        dz.addEventListener('drop', (e) => {
            e.preventDefault();
            dz.classList.remove('border-salti-yellow', 'bg-salti-yellow/10');
            for (const file of e.dataTransfer.files) uploadFile(file, type);
        });
        input.addEventListener('change', () => {
            for (const file of input.files) uploadFile(file, type);
            input.value = '';
        });
    });

    async function uploadFile(file, type = 'autre') {
        if (file.size > 10 * 1024 * 1024) {
            alert(`Le fichier "${file.name}" dépasse 10 Mo`);
            return;
        }
        const fd = new FormData();
        fd.append('file', file);
        fd.append('type', type);
        fd.append('label', file.name);

        // Cible la liste correspondant au type pour l'indicateur de progression
        const targetList = document.querySelector(`[data-files-list="${type}"]`)
            || document.querySelector('[data-files-list="autre"]');

        const tempLine = document.createElement('div');
        tempLine.className = 'flex items-center gap-2 p-2 bg-blue-50 border border-blue-200 rounded text-xs';
        tempLine.innerHTML = `<span>⏳</span><span>Upload en cours : ${file.name}</span>`;
        targetList.appendChild(tempLine);

        try {
            const r = await fetch(`/p/${TOKEN}/upload`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: fd,
            });
            const d = await r.json();
            tempLine.remove();
            if (d.error) { alert(d.error); return; }

            // Construit la ligne dans la bonne section
            const line = document.createElement('div');
            line.setAttribute('data-doc-id', d.id);
            const isMini = type !== 'autre';
            line.className = isMini
                ? 'flex items-center justify-between gap-3 p-2 bg-gray-50 border border-gray-200 rounded'
                : 'flex items-center justify-between gap-3 p-3 bg-gray-50 border border-gray-200 rounded';
            line.innerHTML = `
                <div class="flex items-center gap-2 min-w-0 flex-1">
                    <span class="${isMini ? 'text-lg' : 'text-xl'} shrink-0">📄</span>
                    <div class="min-w-0">
                        <div class="${isMini ? 'text-xs' : 'text-sm'} font-medium truncate" data-name></div>
                        <div class="text-xs text-gray-500">${(d.size / 1024).toFixed(0)} Ko</div>
                    </div>
                </div>
                <div class="flex gap-2 shrink-0">
                    <a href="${d.download_url}" class="${isMini ? 'text-xs' : 'text-sm'} text-blue-600 hover:underline">Voir</a>
                    <button type="button" data-delete-btn class="${isMini ? 'text-xs' : 'text-sm'} text-red-600 hover:underline">Supprimer</button>
                </div>`;
            line.querySelector('[data-name]').textContent = d.filename;
            line.querySelector('[data-delete-btn]').addEventListener('click', () => deleteDoc(d.id));
            targetList.appendChild(line);

            // Coche automatiquement la case correspondante si demandé par SALTI
            if (type !== 'autre') {
                const cbPath = type === 'habilitation' ? 'documents_remis_salti.habilitations' : 'documents_remis_salti.' + type;
                const cb = document.querySelector(`[data-cb-path="${cbPath}"]`);
                if (cb && ! cb.checked) {
                    cb.checked = true;
                    cb.dispatchEvent(new Event('change'));
                }
            }
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

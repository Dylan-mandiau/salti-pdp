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
                           maxlength="14" minlength="14" pattern="\d{14}" inputmode="numeric"
                           oninput="this.value = this.value.replace(/\D/g, '').slice(0, 14); this.setCustomValidity(this.value && this.value.length !== 14 ? 'Le SIRET doit comporter exactement 14 chiffres.' : '')"
                           placeholder="14 chiffres"
                           class="pdp-siret-input w-full border border-gray-300 rounded px-3 py-2 text-sm font-mono invalid:border-red-400 invalid:bg-red-50">
                    <p class="text-xs text-red-600 mt-1 hidden pdp-siret-error">Le SIRET doit comporter exactement 14 chiffres.</p>
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
                                                <a href="{{ route('prestataire.download-document', ['token' => $token, 'doc' => $doc->id]) }}?inline=1" target="_blank" class="text-xs text-blue-600 hover:underline" title="Consulter">👁</a>
                                                <a href="{{ route('prestataire.download-document', ['token' => $token, 'doc' => $doc->id]) }}" class="text-xs text-blue-600 hover:underline" title="Télécharger">📥</a>
                                                <button type="button" onclick="deleteDoc({{ $doc->id }})" class="text-xs text-red-600 hover:underline" title="Supprimer">🗑</button>
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
                                <a href="{{ route('prestataire.download-document', ['token' => $token, 'doc' => $doc->id]) }}?inline=1" target="_blank" class="text-sm text-blue-600 hover:underline" title="Consulter">👁 <span class="hidden sm:inline">Consulter</span></a>
                                <a href="{{ route('prestataire.download-document', ['token' => $token, 'doc' => $doc->id]) }}" class="text-sm text-blue-600 hover:underline" title="Télécharger">📥 <span class="hidden sm:inline">Télécharger</span></a>
                                <button type="button" onclick="deleteDoc({{ $doc->id }})" class="text-sm text-red-600 hover:underline" title="Supprimer">🗑</button>
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
                $empCount = max(1, $nbSalaries, $habs->count());
                $recommendedCodes = $pdp->recommendedHabilitations();
                $habCatalog = \App\Models\Pdp::HABILITATIONS_LIST;
            @endphp
            <p class="text-sm text-gray-600 mb-3">
                Renseignez les salariés qui interviendront sur le site SALTI <strong>avec toutes leurs habilitations valides</strong>.
                Un salarié peut avoir plusieurs habilitations (CACES, habilitations électriques, SST, etc.).
                @if($nbSalaries > 0)
                    <br><span class="text-blue-700">SALTI a indiqué <strong>{{ $nbSalaries }} salarié{{ $nbSalaries > 1 ? 's' : '' }} affecté{{ $nbSalaries > 1 ? 's' : '' }}</strong>.</span>
                @endif
            </p>

            {{-- Catalogue d'habilitations groupées par catégorie pour la modal --}}
            @php
                $habByCategory = [];
                foreach ($habCatalog as $code => [$label, $cat, $ref]) {
                    $habByCategory[$cat][$code] = $label;
                }
                ksort($habByCategory);
            @endphp

            {{-- Liste des salariés avec leurs habilitations --}}
            <div class="space-y-4" id="emp-list">
                @for($i = 0; $i < $empCount; $i++)
                    @php
                        $h = $habs->get($i);
                        $habList = $h?->habilitations_list ?? [];
                        if (empty($habList)) {
                            $habList = [['code' => null, 'label' => '', 'validity' => null]];
                        }
                    @endphp
                    <div data-emp-card class="border border-gray-200 rounded-lg p-3 bg-gray-50">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-xs font-semibold text-gray-500">Salarié #{{ $i + 1 }}</div>
                            <button type="button" onclick="removeEmpCard(this)" class="text-red-500 hover:text-red-700 text-sm border border-red-200 hover:bg-red-50 rounded px-2 py-0.5" title="Supprimer le salarié">🗑 Supprimer</button>
                        </div>
                        <input type="text" data-emp-field="nom_prenom" value="{{ $h?->nom_prenom ?? '' }}"
                               placeholder="Nom Prénom du salarié"
                               class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-medium mb-3">
                        <div class="text-xs font-semibold text-gray-600 mb-1">Habilitations :</div>
                        <div class="space-y-2" data-hab-list>
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
                                    <input type="date" data-hab-field="validity" value="{{ $habItem['validity'] ?? '' }}"
                                           class="border border-gray-200 rounded px-2 py-1.5 text-sm" title="Date de fin de validité">
                                    <button type="button" onclick="removeHabFromEmp(this)" class="text-red-500 hover:text-red-700 text-xl justify-self-center" title="Retirer cette habilitation">×</button>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" onclick="addHabToEmp(this)"
                                class="mt-2 inline-flex items-center gap-1 px-2 py-1 border border-gray-300 rounded text-xs hover:bg-white hover:border-salti-yellow transition">
                            + Ajouter une habilitation
                        </button>
                    </div>
                @endfor
            </div>

            {{-- Modal habilitations (partial réutilisable presta + SALTI) --}}
            @include('pdp.partials.hab-picker-modal')

            <button type="button" onclick="addEmpCard()"
                    class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 border border-gray-300 rounded text-sm hover:bg-gray-50 hover:border-salti-yellow transition">
                <span class="text-lg">+</span> Ajouter un salarié
            </button>
            <p class="text-xs text-gray-500 mt-2">⚠ Les habilitations doivent être valides à la date de début de l'intervention.</p>
        </div>

        {{-- Permis feu — apparaît seulement si la case est cochée par SALTI.
             Partial unifié, identique côté SALTI (étape 5) — convention data-path. --}}
        @if($data['documents_remis_ee']['permis_feu'] ?? false)
            @include('pdp.partials.permis-feu-form', [
                'pf' => $data['permis_feu'] ?? [],
                'downloadUrl' => route('prestataire.download-permis-feu', $token),
                'audience' => 'presta',
            ])
        @endif

        {{-- L'attestation de prise de connaissance a été déplacée tout en bas de la page,
             juste avant le bouton « Soumettre à SALTI » / la signature finale, pour que
             le presta finisse par signer après avoir tout rempli ET avoir consulté le PDP. --}}

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

        {{-- ═══════════════════════════════════════════════════════════════
             ATTESTATION FINALE — chaque salarié signe ICI, à la fin du remplissage
             ═══════════════════════════════════════════════════════════════ --}}
        @if($habs->count() > 0)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <h2 class="font-semibold mb-2">📝 Attestation de prise de connaissance du Plan de Prévention</h2>
                <p class="text-sm text-gray-600 mb-3">
                    Chaque salarié intervenant doit signer ci-dessous pour attester avoir pris
                    connaissance du présent plan de prévention, des risques et des mesures associées.
                </p>

                {{-- Bloc consultation du PDP : avant de signer, on doit pouvoir le lire --}}
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                    <p class="text-sm font-medium text-blue-900 mb-2">
                        ℹ Avant de signer, consultez le Plan de Prévention complet :
                    </p>
                    <a href="{{ route('prestataire.download-main-pdp', $token) }}?inline=1" target="_blank"
                       class="inline-flex items-center gap-2 bg-white hover:bg-blue-100 border border-blue-300 text-blue-900 text-sm font-medium px-3 py-1.5 rounded">
                        👁 Consulter le Plan de Prévention
                    </a>
                    @if($data['documents_remis_ee']['permis_feu'] ?? false)
                        <a href="{{ route('prestataire.download-permis-feu', $token) }}?inline=1" target="_blank"
                           class="inline-flex items-center gap-2 bg-white hover:bg-blue-100 border border-blue-300 text-blue-900 text-sm font-medium px-3 py-1.5 rounded ml-1">
                            🔥 Voir le Permis feu
                        </a>
                    @endif
                    @if($data['documents_remis_ee']['convention_pret'] ?? false)
                        <a href="{{ route('prestataire.download-convention-pret', $token) }}?inline=1" target="_blank"
                           class="inline-flex items-center gap-2 bg-white hover:bg-blue-100 border border-blue-300 text-blue-900 text-sm font-medium px-3 py-1.5 rounded ml-1">
                            📋 Voir la Convention
                        </a>
                    @endif
                </div>

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
                                <canvas id="sig-iv-{{ $iv->id }}" class="border-2 border-dashed border-gray-300 rounded w-full bg-white touch-none" style="min-height:200px;height:35vh;max-height:300px"></canvas>
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
                        <canvas id="sig-ee" class="border-2 border-dashed border-gray-300 rounded w-full bg-white touch-none" style="min-height:240px;height:40vh;max-height:360px"></canvas>
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
                        <div class="flex gap-1.5 flex-shrink-0">
                            <a href="{{ route('prestataire.download-main-pdp', $token) }}?inline=1" target="_blank"
                               class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium px-3 py-1.5 rounded whitespace-nowrap"
                               title="Consulter dans un nouvel onglet">👁 <span class="hidden sm:inline">Consulter</span></a>
                            @if($pdp->signed_by_prestataire_at)
                                <a href="{{ route('prestataire.download-main-pdp', $token) }}"
                                   class="bg-salti-yellow hover:brightness-95 text-black text-sm font-semibold px-3 py-1.5 rounded whitespace-nowrap"
                                   title="Télécharger">📥 <span class="hidden sm:inline">Télécharger</span></a>
                            @endif
                        </div>
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
                            <div class="flex gap-1.5 flex-shrink-0">
                                <a href="{{ route('prestataire.download-plan-acces', $token) }}?inline=1" target="_blank"
                                   class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium px-3 py-1.5 rounded whitespace-nowrap"
                                   title="Consulter">👁 <span class="hidden sm:inline">Consulter</span></a>
                                <a href="{{ route('prestataire.download-plan-acces', $token) }}"
                                   class="bg-salti-yellow hover:brightness-95 text-black text-sm font-semibold px-3 py-1.5 rounded whitespace-nowrap"
                                   title="Télécharger">📥 <span class="hidden sm:inline">Télécharger</span></a>
                            </div>
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
                            <div class="flex gap-1.5 flex-shrink-0">
                                <a href="{{ route('prestataire.download-permis-feu', $token) }}?inline=1" target="_blank"
                                   class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium px-3 py-1.5 rounded whitespace-nowrap"
                                   title="Consulter">👁 <span class="hidden sm:inline">Consulter</span></a>
                                <a href="{{ route('prestataire.download-permis-feu', $token) }}"
                                   class="bg-salti-yellow hover:brightness-95 text-black text-sm font-semibold px-3 py-1.5 rounded whitespace-nowrap"
                                   title="Télécharger">📥 <span class="hidden sm:inline">Télécharger</span></a>
                            </div>
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
                            <div class="flex gap-1.5 flex-shrink-0">
                                <a href="{{ route('prestataire.download-convention-pret', $token) }}?inline=1" target="_blank"
                                   class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium px-3 py-1.5 rounded whitespace-nowrap"
                                   title="Consulter">👁 <span class="hidden sm:inline">Consulter</span></a>
                                <a href="{{ route('prestataire.download-convention-pret', $token) }}"
                                   class="bg-salti-yellow hover:brightness-95 text-black text-sm font-semibold px-3 py-1.5 rounded whitespace-nowrap"
                                   title="Télécharger">📥 <span class="hidden sm:inline">Télécharger</span></a>
                            </div>
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

    // Auto-save : déclenche sur input/change/blur — temps réel agressif
    // (300ms de debounce + blur instantané pour éviter la perte de données
    // quand le presta change de page ou ferme l'onglet)
    let saveTimer;
    function attachAutoSave(el) {
        ['input', 'change'].forEach(evt => el.addEventListener(evt, () => {
            clearTimeout(saveTimer);
            saveTimer = setTimeout(autoSave, 300);
        }));
        el.addEventListener('blur', () => {
            clearTimeout(saveTimer);
            autoSave();
        });
    }
    document.querySelectorAll('[data-path], [data-cb-path], .ee-radio, [data-emp-card] input, [data-emp-card] select, [data-ar-row]').forEach(attachAutoSave);

    // La modal habilitations + ses fonctions (openHabPicker, selectHabFromPicker, etc.)
    // sont maintenant dans pdp/partials/hab-picker-modal.blade.php (partagé avec SALTI).
    // À chaque sélection, la modal émet l'événement 'hab-changed' qui bubble depuis la ligne.
    document.addEventListener('hab-changed', () => {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(autoSave, 300);
    });

    /** Construit une ligne <habilitation> (bouton qui ouvre la modal + validity + bouton ✕) */
    function buildHabLineEl(code = '', label = '', validity = '') {
        const wrap = document.createElement('div');
        wrap.setAttribute('data-hab-line', '');
        wrap.className = 'grid grid-cols-1 md:grid-cols-[1fr_180px_40px] gap-2 md:items-center bg-white p-2 rounded border border-gray-200';
        const safeLabel = (label || '').replace(/"/g, '&quot;').replace(/</g, '&lt;');
        wrap.innerHTML = `
            <button type="button" onclick="openHabPicker(this)"
                    class="w-full text-left border border-gray-300 rounded px-3 py-2 text-sm hover:border-salti-yellow hover:bg-yellow-50 flex items-center justify-between gap-2 min-h-[38px]">
                <span data-hab-display class="truncate ${label ? 'text-gray-900 font-medium' : 'text-gray-400'}">
                    ${safeLabel || '— Choisir une habilitation —'}
                </span>
                <span class="text-gray-400 shrink-0 text-xs">▾</span>
            </button>
            <input type="hidden" data-hab-field="label" value="${safeLabel}">
            <input type="hidden" data-hab-field="code" value="${code}">
            <input type="date" data-hab-field="validity" value="${validity}" class="border border-gray-200 rounded px-2 py-1.5 text-sm" title="Date de fin de validité">
            <button type="button" onclick="removeHabFromEmp(this)" class="text-red-500 hover:text-red-700 text-xl justify-self-center" title="Retirer cette habilitation">×</button>
        `;
        wrap.querySelectorAll('input').forEach(attachAutoSave);
        return wrap;
    }

    /** Construit une carte <salarié> (nom + liste d'habilitations + bouton +) */
    function buildEmpCardEl(idx) {
        const card = document.createElement('div');
        card.setAttribute('data-emp-card', '');
        card.className = 'border border-gray-200 rounded-lg p-3 bg-gray-50';
        card.innerHTML = `
            <div class="flex items-center justify-between mb-2">
                <div class="text-xs font-semibold text-gray-500">Salarié #${idx + 1}</div>
                <button type="button" onclick="removeEmpCard(this)" class="text-red-500 hover:text-red-700 text-sm border border-red-200 hover:bg-red-50 rounded px-2 py-0.5">🗑 Supprimer</button>
            </div>
            <input type="text" data-emp-field="nom_prenom" placeholder="Nom Prénom du salarié" class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-medium mb-3">
            <div class="text-xs font-semibold text-gray-600 mb-1">Habilitations :</div>
            <div class="space-y-2" data-hab-list></div>
            <button type="button" onclick="addHabToEmp(this)" class="mt-2 inline-flex items-center gap-1 px-2 py-1 border border-gray-300 rounded text-xs hover:bg-white hover:border-salti-yellow transition">+ Ajouter une habilitation</button>
        `;
        card.querySelector('[data-hab-list]').appendChild(buildHabLineEl());
        card.querySelectorAll('input').forEach(attachAutoSave);
        return card;
    }

    window.addEmpCard = function() {
        const container = document.getElementById('emp-list');
        const idx = container.querySelectorAll('[data-emp-card]').length;
        const card = buildEmpCardEl(idx);
        container.appendChild(card);
        card.querySelector('[data-emp-field="nom_prenom"]').focus();
        // Re-numérote les libellés "Salarié #N"
        renumberEmpCards();
    };

    window.removeEmpCard = function(btn) {
        const container = document.getElementById('emp-list');
        const card = btn.closest('[data-emp-card]');
        if (container.querySelectorAll('[data-emp-card]').length <= 1) {
            card.querySelectorAll('input').forEach(i => i.value = '');
        } else {
            card.remove();
        }
        renumberEmpCards();
        clearTimeout(saveTimer);
        saveTimer = setTimeout(autoSave, 200);
    };

    window.addHabToEmp = function(btn) {
        const card = btn.closest('[data-emp-card]');
        const list = card.querySelector('[data-hab-list]');
        const line = buildHabLineEl();
        list.appendChild(line);
        line.querySelector('input').focus();
    };

    window.removeHabFromEmp = function(btn) {
        const list = btn.closest('[data-hab-list]');
        const line = btn.closest('[data-hab-line]');
        if (list.querySelectorAll('[data-hab-line]').length <= 1) {
            line.querySelectorAll('input').forEach(i => i.value = '');
        } else {
            line.remove();
        }
        clearTimeout(saveTimer);
        saveTimer = setTimeout(autoSave, 200);
    };

    function renumberEmpCards() {
        document.querySelectorAll('[data-emp-card]').forEach((c, i) => {
            const lbl = c.querySelector('.text-gray-500');
            if (lbl) lbl.textContent = `Salarié #${i + 1}`;
        });
    }

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

        // 4. Salariés et leurs habilitations multiples
        const intervenants = [];
        document.querySelectorAll('[data-emp-card]').forEach(card => {
            const nom = (card.querySelector('[data-emp-field="nom_prenom"]')?.value || '').trim();
            const habs = [];
            card.querySelectorAll('[data-hab-line]').forEach(line => {
                const label = (line.querySelector('[data-hab-field="label"]')?.value || '').trim();
                if (! label) return;
                habs.push({
                    code: line.querySelector('[data-hab-field="code"]')?.value || null,
                    label,
                    validity: line.querySelector('[data-hab-field="validity"]')?.value || null,
                });
            });
            if (nom || habs.length) {
                intervenants.push({ nom_prenom: nom, habilitations: habs });
            }
        });
        data.intervenants = intervenants;

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

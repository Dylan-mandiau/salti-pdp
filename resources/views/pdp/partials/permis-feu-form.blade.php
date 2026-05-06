{{--
    Partial réutilisable : formulaire Permis feu en ligne.
    Utilisé côté prestataire (lien magique) ET côté SALTI (étape 5 du wizard,
    en présentiel ou pour corriger en distance).

    Utilise la convention data-path / data-cb-path qui est lue par les deux JS
    auto-save (côté presta : autoSave dans show.blade.php, côté SALTI : save()
    dans pdpWizard()). Pas de différence à gérer entre les contextes.

    Variables attendues :
      $pf            — array : $data['permis_feu'] ?? []
      $downloadUrl   — string : URL pour télécharger le PDF Permis feu pré-rempli
                                 (route presta ou SALTI selon le contexte)
      $audience      — 'presta' | 'salti' : ajuste juste les libellés
                       (« vous » côté presta, « avec le presta » côté SALTI)
--}}
@php
    $audience = $audience ?? 'presta';
    $pf = $pf ?? [];
    $mes = $pf['mise_en_securite'] ?? [];
    $mp = $pf['moyens_prevention'] ?? [];
    $isSalti = $audience === 'salti';
@endphp

<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6"
     x-data="{ mode: '{{ $pf['mode_remplissage'] ?? ($isSalti ? 'online' : 'paper') }}' }">
    <h2 class="font-semibold mb-2">🔥 Permis feu</h2>
    <p class="text-sm text-gray-600 mb-3">
        Ce document est requis pour les travaux par points chauds (soudure, meulage, découpe, etc.).
    </p>

    <div class="bg-yellow-50 border border-yellow-200 rounded p-3 mb-4">
        <p class="text-sm font-medium mb-2">
            @if($isSalti)
                Mode de remplissage du Permis feu :
            @else
                Comment souhaitez-vous compléter le Permis feu ?
            @endif
        </p>
        <div class="flex flex-col sm:flex-row gap-3">
            <label class="flex items-start gap-2 cursor-pointer flex-1 p-3 border rounded {{ ($pf['mode_remplissage'] ?? '') === 'paper' ? 'border-salti-yellow bg-white' : 'border-gray-200' }}">
                <input type="radio" data-cb-path="permis_feu.mode_remplissage" name="pf_mode_{{ $audience }}" value="paper" x-model="mode" class="mt-0.5">
                <span class="text-sm">
                    <strong>📄 Papier</strong><br>
                    @if($isSalti)
                        Le presta téléchargera le PDF pré-rempli, le complètera à la main, le scannera et l'uploadera.
                    @else
                        Je télécharge le PDF pré-rempli, je l'imprime, je le complète à la main, le fais signer, scan + upload via la zone "Documents".
                    @endif
                </span>
            </label>
            <label class="flex items-start gap-2 cursor-pointer flex-1 p-3 border rounded {{ ($pf['mode_remplissage'] ?? '') === 'online' ? 'border-salti-yellow bg-white' : 'border-gray-200' }}">
                <input type="radio" data-cb-path="permis_feu.mode_remplissage" name="pf_mode_{{ $audience }}" value="online" x-model="mode" class="mt-0.5">
                <span class="text-sm">
                    <strong>💻 En ligne</strong><br>
                    @if($isSalti)
                        Vous remplissez les champs ci-dessous (en présentiel avec le presta), le PDF se génère automatiquement.
                    @else
                        Je remplis directement les champs ci-dessous, le PDF se génère automatiquement.
                    @endif
                </span>
            </label>
        </div>
    </div>

    {{-- Mode "papier" : lien de téléchargement --}}
    <div x-show="mode === 'paper'" class="bg-blue-50 border border-blue-200 rounded p-3 mb-3 text-sm">
        <p class="font-medium mb-1">📥 Téléchargez le PDF Permis feu pré-rempli :</p>
        <a href="{{ $downloadUrl }}" class="inline-block mt-2 bg-salti-yellow hover:bg-salti-yellow-dark text-black font-semibold px-4 py-2 rounded">
            📄 Télécharger le Permis feu pré-rempli
        </a>
        <p class="text-xs text-gray-600 mt-2">Une fois rempli + signé, scannez ou photographiez le document et uploadez-le dans la section « Documents ».</p>
    </div>

    {{-- Mode "en ligne" : formulaire complet --}}
    <div x-show="mode === 'online'" x-cloak class="space-y-3">
        <div>
            <label class="block text-sm font-medium mb-1">Mode opératoire (référence)</label>
            <input type="text" data-path="permis_feu.mode_operatoire" value="{{ $pf['mode_operatoire'] ?? '' }}" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Liste des opérateurs autorisés</label>
            <textarea data-path="permis_feu.operateurs_autorises" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" rows="3" placeholder="Auto-rempli depuis les salariés intervenants si laissé vide">{{ $pf['operateurs_autorises'] ?? '' }}</textarea>
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

        {{-- Tableaux Mise en sécurité + Moyens de prévention --}}
        @php
            $mesHasData = collect(\App\Models\Pdp::PERMIS_FEU_MISE_EN_SECURITE)->keys()->some(
                fn($k) => !empty($mes[$k]['a_faire']) || !empty($mes[$k]['qui']) || !empty($mes[$k]['fait'])
            );
            $mpHasData = collect(\App\Models\Pdp::PERMIS_FEU_MOYENS_PREVENTION)->keys()->some(
                fn($k) => !empty($mp[$k]['a_faire']) || !empty($mp[$k]['qui']) || !empty($mp[$k]['fait'])
            );
        @endphp

        <details class="border border-gray-200 rounded-lg mt-3" {{ $mesHasData ? 'open' : '' }}>
            <summary class="font-medium text-sm cursor-pointer p-3 hover:bg-gray-50">🛡 Mise en sécurité ({{ count(\App\Models\Pdp::PERMIS_FEU_MISE_EN_SECURITE) }} mesures)</summary>
            <div class="px-3 pb-3">
                <p class="text-xs text-gray-500 mb-2">Pour chaque mesure : indiquez si elle est à faire / qui s'en charge / si elle est faite et quand.</p>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="text-gray-600 border-b border-gray-200">
                                <th class="text-left py-1 pr-2">Mesure</th>
                                <th class="px-1 whitespace-nowrap">À faire ?</th>
                                <th class="px-1">Qui ?</th>
                                <th class="px-1 whitespace-nowrap">Fait ?</th>
                                <th class="px-1 whitespace-nowrap">Le ?</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(\App\Models\Pdp::PERMIS_FEU_MISE_EN_SECURITE as $slug => $label)
                                @php $r = $mes[$slug] ?? []; @endphp
                                <tr class="border-b border-gray-100 align-top">
                                    <td class="py-1.5 pr-2">{{ $label }}</td>
                                    <td class="px-1">
                                        <select data-path="permis_feu.mise_en_securite.{{ $slug }}.a_faire" class="border border-gray-200 rounded text-xs px-1 py-0.5">
                                            <option value="non" @selected(($r['a_faire'] ?? 'non') !== 'oui')>NON</option>
                                            <option value="oui" @selected(($r['a_faire'] ?? null) === 'oui')>OUI</option>
                                        </select>
                                    </td>
                                    <td class="px-1"><input type="text" data-path="permis_feu.mise_en_securite.{{ $slug }}.qui" value="{{ $r['qui'] ?? '' }}" class="border border-gray-200 rounded text-xs px-1 py-0.5 w-24"></td>
                                    <td class="px-1">
                                        <select data-path="permis_feu.mise_en_securite.{{ $slug }}.fait" class="border border-gray-200 rounded text-xs px-1 py-0.5">
                                            <option value="non" @selected(($r['fait'] ?? 'non') !== 'oui')>NON</option>
                                            <option value="oui" @selected(($r['fait'] ?? null) === 'oui')>OUI</option>
                                        </select>
                                    </td>
                                    <td class="px-1"><input type="date" data-path="permis_feu.mise_en_securite.{{ $slug }}.fait_le" value="{{ $r['fait_le'] ?? '' }}" class="border border-gray-200 rounded text-xs px-1 py-0.5"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </details>

        <details class="border border-gray-200 rounded-lg mt-3" {{ $mpHasData ? 'open' : '' }}>
            <summary class="font-medium text-sm cursor-pointer p-3 hover:bg-gray-50">🚒 Moyens de prévention ({{ count(\App\Models\Pdp::PERMIS_FEU_MOYENS_PREVENTION) }} moyens)</summary>
            <div class="px-3 pb-3">
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="text-gray-600 border-b border-gray-200">
                                <th class="text-left py-1 pr-2">Moyen</th>
                                <th class="px-1 whitespace-nowrap">À faire ?</th>
                                <th class="px-1">Qui ?</th>
                                <th class="px-1 whitespace-nowrap">Fait ?</th>
                                <th class="px-1 whitespace-nowrap">Le ?</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(\App\Models\Pdp::PERMIS_FEU_MOYENS_PREVENTION as $slug => $label)
                                @php $r = $mp[$slug] ?? []; @endphp
                                <tr class="border-b border-gray-100 align-top">
                                    <td class="py-1.5 pr-2">{{ $label }}</td>
                                    <td class="px-1">
                                        <select data-path="permis_feu.moyens_prevention.{{ $slug }}.a_faire" class="border border-gray-200 rounded text-xs px-1 py-0.5">
                                            <option value="non" @selected(($r['a_faire'] ?? 'non') !== 'oui')>NON</option>
                                            <option value="oui" @selected(($r['a_faire'] ?? null) === 'oui')>OUI</option>
                                        </select>
                                    </td>
                                    <td class="px-1"><input type="text" data-path="permis_feu.moyens_prevention.{{ $slug }}.qui" value="{{ $r['qui'] ?? '' }}" class="border border-gray-200 rounded text-xs px-1 py-0.5 w-24"></td>
                                    <td class="px-1">
                                        <select data-path="permis_feu.moyens_prevention.{{ $slug }}.fait" class="border border-gray-200 rounded text-xs px-1 py-0.5">
                                            <option value="non" @selected(($r['fait'] ?? 'non') !== 'oui')>NON</option>
                                            <option value="oui" @selected(($r['fait'] ?? null) === 'oui')>OUI</option>
                                        </select>
                                    </td>
                                    <td class="px-1"><input type="date" data-path="permis_feu.moyens_prevention.{{ $slug }}.fait_le" value="{{ $r['fait_le'] ?? '' }}" class="border border-gray-200 rounded text-xs px-1 py-0.5"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </details>

        <div class="grid grid-cols-1 md:grid-cols-[1fr_140px] gap-3 mt-3">
            <div>
                <label class="block text-sm font-medium mb-1">Surveillance pendant les travaux — Nom</label>
                <input type="text" data-path="permis_feu.surveillance_pendant" value="{{ $pf['surveillance_pendant'] ?? '' }}" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Visa</label>
                <input type="text" data-path="permis_feu.surveillance_pendant_visa" value="{{ $pf['surveillance_pendant_visa'] ?? '' }}" placeholder="Initiales" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-[1fr_80px_80px_140px] gap-3 mt-3">
            <div>
                <label class="block text-sm font-medium mb-1">Surveillance après les travaux — Nom</label>
                <input type="text" data-path="permis_feu.surveillance_apres_nom" value="{{ $pf['surveillance_apres_nom'] ?? '' }}" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">À partir de (h)</label>
                <input type="text" data-path="permis_feu.surveillance_apres_de" value="{{ $pf['surveillance_apres_de'] ?? '' }}" placeholder="17" maxlength="5" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Jusqu'à (h)</label>
                <input type="text" data-path="permis_feu.surveillance_apres_a" value="{{ $pf['surveillance_apres_a'] ?? '' }}" placeholder="19" maxlength="5" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Visa</label>
                <input type="text" data-path="permis_feu.surveillance_apres_visa" value="{{ $pf['surveillance_apres_visa'] ?? '' }}" placeholder="Initiales" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
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

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
            <div>
                <label class="block text-sm font-medium mb-1">Permis feu délivré le</label>
                <input type="date" data-path="permis_feu.date_delivrance" value="{{ $pf['date_delivrance'] ?? '' }}" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                <p class="text-xs text-gray-500 mt-1">Auto-rempli avec la date du jour quand le presta signera le PDP.</p>
            </div>
            <div class="flex items-end">
                <p class="text-xs text-gray-600 bg-blue-50 border border-blue-200 rounded p-2">
                    ✍ <strong>Signature de l'employeur :</strong> la signature du presta sur le Plan de Prévention sera automatiquement reportée sur le Permis feu — pas besoin de signer deux fois.
                </p>
            </div>
        </div>

        <p class="text-xs text-gray-500 mt-2">💡 Le PDF Permis feu est régénéré automatiquement à chaque modification.</p>
        <a href="{{ $downloadUrl }}" class="inline-block mt-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded text-sm">
            📄 Télécharger le Permis feu
        </a>
    </div>
</div>

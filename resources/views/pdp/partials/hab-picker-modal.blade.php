{{--
    Partial réutilisable : modal de sélection d'habilitation avec recherche.
    Utilisé côté prestataire ET côté SALTI (étape 5) pour rester cohérent.

    Inclusion :
        @include('pdp.partials.hab-picker-modal')

    Pré-requis : Alpine.js + Tailwind chargés sur la page.

    API JS exposée :
        window.openHabPicker(buttonEl)             — ouvre la modal pour la ligne data-hab-line conteneuse
        window.selectHabFromPicker(code, label)    — appelée par les items
        window.selectCustomFromPicker(label)       — appelée par le bouton « Valider » de la saisie libre
        window.filterHabPicker(query)              — filtre live à chaque keystroke

    Chaque ligne <div data-hab-line> doit contenir :
        - <span data-hab-display>          : libellé affiché dans le bouton
        - <input data-hab-field="label">   : libellé canonique (hidden ou texte)
        - <input data-hab-field="code">    : code interne (hidden)

    À chaque sélection, la modal :
        1) remplit les data-hab-field
        2) met à jour data-hab-display
        3) émet un CustomEvent 'hab-changed' qui bubble depuis la ligne,
           pour que la page hôte déclenche son propre auto-save / upsert.
--}}
@php
    $habCatalog = \App\Models\Pdp::HABILITATIONS_LIST;
    $habByCategory = [];
    foreach ($habCatalog as $code => [$label, $cat, $ref]) {
        $habByCategory[$cat][$code] = $label;
    }
    ksort($habByCategory);
@endphp

<div id="hab-picker-modal"
     x-data="{ open: false, search: '', customMode: false, customLabel: '' }"
     x-show="open" x-cloak
     @keydown.escape.window="open = false"
     class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center"
     style="display:none">
    <div @click="open = false" class="absolute inset-0 bg-black/50"></div>
    <div class="relative bg-white rounded-t-2xl sm:rounded-2xl shadow-xl w-full sm:max-w-2xl max-h-[88vh] flex flex-col">
        <div class="p-4 border-b border-gray-200">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-gray-900">Choisir une habilitation</h3>
                <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-700 text-3xl leading-none w-8 h-8" aria-label="Fermer">×</button>
            </div>
            <input type="text" x-model="search" oninput="filterHabPicker(this.value)"
                   placeholder="🔍 Rechercher (CACES, B2V, harnais, ATEX…)"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-salti-yellow focus:ring-1 focus:ring-salti-yellow"
                   x-show="!customMode">
        </div>

        {{-- Liste groupée par catégorie --}}
        <div class="flex-1 overflow-y-auto px-2 py-2" id="hab-picker-results" x-show="!customMode">
            @foreach($habByCategory as $cat => $items)
                <div data-hab-cat class="mb-2">
                    <div class="text-xs font-bold text-gray-600 uppercase tracking-wide px-2 py-1.5 sticky top-0 bg-gray-50 rounded">
                        {{ $cat }} <span class="text-gray-400 font-normal" data-hab-cat-count>({{ count($items) }})</span>
                    </div>
                    @foreach($items as $code => $label)
                        <button type="button" data-hab-item data-hab-code="{{ $code }}" data-hab-label="{{ $label }}"
                                onclick="selectHabFromPicker(this.dataset.habCode, this.dataset.habLabel)"
                                class="w-full text-left px-3 py-2 rounded hover:bg-blue-50 text-sm flex items-center justify-between group transition">
                            <span>{{ $label }}</span>
                            <span class="text-xs text-gray-300 group-hover:text-blue-500">→</span>
                        </button>
                    @endforeach
                </div>
            @endforeach
            <div data-hab-noresults class="text-center text-sm text-gray-500 italic py-8 hidden">
                Aucune habilitation ne correspond. Essayez un autre mot ou utilisez la <strong>saisie libre</strong>.
            </div>
        </div>

        {{-- Mode saisie libre --}}
        <div class="flex-1 overflow-y-auto p-4" x-show="customMode">
            <p class="text-sm text-gray-600 mb-2">Saisissez l'habilitation telle qu'elle figure sur le titre / certificat :</p>
            <input type="text" x-model="customLabel" placeholder="ex. Habilitation Drone &lt; 25 kg"
                   class="w-full border border-gray-300 rounded px-3 py-2 mb-3"
                   @keydown.enter="if(customLabel.trim()) { selectCustomFromPicker(customLabel.trim()); }">
        </div>

        <div class="p-3 border-t border-gray-200 flex items-center justify-between gap-2">
            <button type="button" @click="customMode = !customMode; customLabel = ''"
                    class="text-sm text-gray-600 hover:text-gray-900 px-2 py-1.5">
                <span x-show="!customMode">✏ Saisie libre</span>
                <span x-show="customMode">← Retour à la liste</span>
            </button>
            <div class="flex gap-2">
                <button type="button" @click="open = false" class="text-sm text-gray-600 px-3 py-1.5 hover:bg-gray-100 rounded">Annuler</button>
                <button type="button" x-show="customMode" @click="if(customLabel.trim()) selectCustomFromPicker(customLabel.trim())"
                        class="bg-salti-yellow hover:brightness-95 text-black font-semibold text-sm px-3 py-1.5 rounded">Valider</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    if (window.__habPickerInitialized) return;
    window.__habPickerInitialized = true;

    let habPickerTarget = null;

    window.openHabPicker = function(buttonEl) {
        habPickerTarget = buttonEl.closest('[data-hab-line]');
        const modal = document.getElementById('hab-picker-modal');
        const data = (window.Alpine && Alpine.$data ? Alpine.$data(modal) : modal.__x?.$data);
        if (data) {
            data.open = true;
            data.search = '';
            data.customMode = false;
            data.customLabel = '';
        }
        filterHabPicker('');
        setTimeout(() => modal.querySelector('input[type="text"]')?.focus(), 50);
    };

    window.selectHabFromPicker = function(code, label) {
        if (! habPickerTarget) return;
        setHabLineValue(habPickerTarget, code, label);
        closeHabPicker();
    };

    window.selectCustomFromPicker = function(label) {
        if (! habPickerTarget) return;
        setHabLineValue(habPickerTarget, '', label);
        closeHabPicker();
    };

    function setHabLineValue(line, code, label) {
        const labelEl = line.querySelector('[data-hab-field="label"]');
        const codeEl = line.querySelector('[data-hab-field="code"]');
        const display = line.querySelector('[data-hab-display]');
        if (labelEl) labelEl.value = label;
        if (codeEl) codeEl.value = code;
        if (display) {
            display.textContent = label || '— Choisir une habilitation —';
            display.classList.toggle('text-gray-400', !label);
            display.classList.toggle('text-gray-900', !!label);
            display.classList.toggle('font-medium', !!label);
        }
        // Émet un événement bubble pour que la page hôte (presta / SALTI)
        // déclenche son propre auto-save / upsert.
        line.dispatchEvent(new CustomEvent('hab-changed', { bubbles: true, detail: { code, label } }));
    }

    function closeHabPicker() {
        const modal = document.getElementById('hab-picker-modal');
        const data = (window.Alpine && Alpine.$data ? Alpine.$data(modal) : modal.__x?.$data);
        if (data) data.open = false;
        habPickerTarget = null;
    }

    window.filterHabPicker = function(query) {
        const q = (query || '').toLowerCase().trim();
        let totalVisible = 0;
        document.querySelectorAll('#hab-picker-results [data-hab-cat]').forEach(cat => {
            let visible = 0;
            cat.querySelectorAll('[data-hab-item]').forEach(item => {
                const text = item.textContent.toLowerCase();
                const match = !q || text.includes(q);
                item.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            cat.style.display = visible > 0 ? '' : 'none';
            const countEl = cat.querySelector('[data-hab-cat-count]');
            if (countEl && q) countEl.textContent = `(${visible})`;
            totalVisible += visible;
        });
        const nores = document.querySelector('[data-hab-noresults]');
        if (nores) nores.classList.toggle('hidden', totalVisible > 0);
    };
})();
</script>

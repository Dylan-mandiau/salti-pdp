@php
    /**
     * Partial input réutilisable pour le wizard PDP.
     *
     * @var string $name      Nom du champ (ex. "ee.phone")
     * @var string $label     Label affiché
     * @var ?string $value    Valeur courante (text/select/email)
     * @var string $type      Type d'input : "text" (défaut) | "date" | "tel" | "email"
     *                        | "number" | "select" | "textarea" | "duree"
     *                        | "time-range" | "checkbox" | "radio-group"
     * @var ?array $options   Pour type=select : ['value' => 'label']
     *                        Pour type=radio-group : ['value' => 'label']
     * @var ?int $maxlength
     * @var ?string $placeholder
     * @var bool $required    Affiche un astérisque rouge à côté du label
     * @var ?string $help     Texte d'aide en petit en dessous
     *
     * Pour type=duree : $value est un array ['value' => int|null, 'unit' => string|null]
     * Pour type=time-range : $value est un array ['debut' => 'HH:MM', 'fin' => 'HH:MM']
     */
    $type = $type ?? 'text';
    $options = $options ?? null;
    $maxlength = $maxlength ?? null;
    $placeholder = $placeholder ?? '';
    $required = $required ?? false;
    $help = $help ?? null;
    $id = 'f_' . str_replace('.', '_', $name);
    $baseClass = 'w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-salti-yellow focus:ring-2 focus:ring-salti-yellow/30 outline-none';
@endphp

<div>
    <label for="{{ $id }}" class="block text-sm font-medium text-gray-700 mb-1">
        {{ $label }}
        @if($required)<span class="text-red-600 ml-0.5">*</span>@endif
    </label>

    @if($type === 'select')
        <select id="{{ $id }}" name="{{ $name }}" class="{{ $baseClass }}" {{ $required ? 'required' : '' }}>
            <option value="">— Choisir —</option>
            @foreach(($options ?? []) as $optValue => $optLabel)
                <option value="{{ $optValue }}" {{ (string)$value === (string)$optValue ? 'selected' : '' }}>
                    {{ $optLabel }}
                </option>
            @endforeach
        </select>

    @elseif($type === 'siret')
        {{-- SIRET : 14 chiffres exactement, filtre live des non-chiffres,
             validité affichée par bordure rouge si saisi mais incomplet --}}
        <input type="text" id="{{ $id }}" name="{{ $name }}"
               value="{{ $value }}"
               maxlength="14" minlength="14"
               pattern="\d{14}" inputmode="numeric"
               oninput="this.value = this.value.replace(/\D/g, '').slice(0, 14); this.setCustomValidity(this.value && this.value.length !== 14 ? 'Le SIRET doit comporter exactement 14 chiffres.' : '')"
               placeholder="{{ $placeholder ?: '14 chiffres' }}"
               class="{{ $baseClass }} font-mono pdp-siret-input invalid:border-red-400 invalid:bg-red-50"
               {{ $required ? 'required' : '' }}>

    @elseif($type === 'tel')
        <input type="tel" id="{{ $id }}" name="{{ $name }}"
               value="{{ $value }}"
               maxlength="{{ $maxlength ?? 20 }}"
               autocomplete="tel"
               placeholder="{{ $placeholder ?: '06 12 34 56 78' }}"
               class="{{ $baseClass }} pdp-tel-input"
               {{ $required ? 'required' : '' }}>

    @elseif($type === 'date')
        <input type="date" id="{{ $id }}" name="{{ $name }}"
               value="{{ $value }}"
               class="{{ $baseClass }}"
               {{ $required ? 'required' : '' }}>

    @elseif($type === 'number')
        <input type="number" id="{{ $id }}" name="{{ $name }}"
               value="{{ $value }}"
               min="0"
               @if($maxlength)maxlength="{{ $maxlength }}"@endif
               placeholder="{{ $placeholder }}"
               class="{{ $baseClass }}"
               {{ $required ? 'required' : '' }}>

    @elseif($type === 'email')
        <input type="email" id="{{ $id }}" name="{{ $name }}"
               value="{{ $value }}"
               autocomplete="email"
               placeholder="{{ $placeholder }}"
               class="{{ $baseClass }}"
               {{ $required ? 'required' : '' }}>

    @elseif($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}"
                  rows="3"
                  placeholder="{{ $placeholder }}"
                  class="{{ $baseClass }}"
                  {{ $required ? 'required' : '' }}>{{ $value }}</textarea>

    @elseif($type === 'duree')
        {{-- Input combiné : nombre + unité.
             Liste simplifiée : 1 seule valeur par unité (toujours au pluriel pour
             éviter les "jour" et "jours" qui doublent). Par défaut "jours" pour
             que le PDF Permis feu / Convention récupère toujours une unité. --}}
        @php
            $durValue = is_array($value) ? ($value['value'] ?? null) : null;
            $durUnit = is_array($value) ? ($value['unit'] ?? null) : null;
            // Migration douce : "jour" → "jours", "semaine" → "semaines", "an" → "ans"
            $durUnit = match ($durUnit) {
                'jour' => 'jours',
                'semaine' => 'semaines',
                'an' => 'ans',
                null, '' => 'jours',
                default => $durUnit,
            };
        @endphp
        <div class="flex gap-2">
            <input type="number" id="{{ $id }}_value"
                   name="{{ $name }}_value"
                   value="{{ $durValue }}"
                   min="0"
                   placeholder="3"
                   class="{{ $baseClass }} flex-1"
                   {{ $required ? 'required' : '' }}>
            <select id="{{ $id }}_unit"
                    name="{{ $name }}_unit"
                    class="{{ $baseClass }} flex-1"
                    {{ $required ? 'required' : '' }}>
                <option value="jours" @selected($durUnit === 'jours')>jour(s)</option>
                <option value="semaines" @selected($durUnit === 'semaines')>semaine(s)</option>
                <option value="mois" @selected($durUnit === 'mois')>mois</option>
                <option value="ans" @selected($durUnit === 'ans')>an(s)</option>
            </select>
        </div>

    @elseif($type === 'time-range')
        @php
            $tDebut = is_array($value) ? ($value['debut'] ?? '') : '';
            $tFin = is_array($value) ? ($value['fin'] ?? '') : '';
        @endphp
        <div class="flex gap-2 items-center">
            <input type="time" id="{{ $id }}_debut" name="{{ $name }}_debut"
                   value="{{ $tDebut }}"
                   class="{{ $baseClass }} flex-1"
                   {{ $required ? 'required' : '' }}>
            <span class="text-gray-500 text-sm">à</span>
            <input type="time" id="{{ $id }}_fin" name="{{ $name }}_fin"
                   value="{{ $tFin }}"
                   class="{{ $baseClass }} flex-1"
                   {{ $required ? 'required' : '' }}>
        </div>

    @elseif($type === 'checkbox')
        <label class="flex items-start gap-2 cursor-pointer">
            <input type="checkbox" name="{{ $name }}" id="{{ $id }}" {{ $value ? 'checked' : '' }}
                   class="mt-0.5 rounded border-gray-300">
            <span class="text-sm">{{ $placeholder ?: $label }}</span>
        </label>

    @else
        <input type="text" id="{{ $id }}" name="{{ $name }}"
               value="{{ $value }}"
               @if($maxlength)maxlength="{{ $maxlength }}"@endif
               placeholder="{{ $placeholder }}"
               class="{{ $baseClass }}"
               {{ $required ? 'required' : '' }}>
    @endif

    @if($help)
        <p class="text-xs text-gray-500 mt-1">{{ $help }}</p>
    @endif
</div>

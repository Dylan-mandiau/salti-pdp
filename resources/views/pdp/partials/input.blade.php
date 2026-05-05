@php
    /**
     * Partial input réutilisable pour le wizard PDP.
     *
     * @var string $name      Nom du champ (ex. "ee.phone")
     * @var string $label     Label affiché
     * @var ?string $value    Valeur courante
     * @var string $type      Type d'input : "text" (défaut) | "date" | "tel" | "email" | "number" | "select"
     * @var ?array $options   Pour type=select : ['value' => 'label']
     * @var ?int $maxlength   Limite de caractères (utile pour les téléphones)
     * @var ?string $placeholder
     */
    $type = $type ?? 'text';
    $options = $options ?? null;
    $maxlength = $maxlength ?? null;
    $placeholder = $placeholder ?? '';
    $id = 'f_' . str_replace('.', '_', $name);
    $baseClass = 'w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-salti-yellow focus:ring-2 focus:ring-salti-yellow/30 outline-none';
@endphp

<div>
    <label for="{{ $id }}" class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>

    @if($type === 'select')
        <select id="{{ $id }}" name="{{ $name }}" class="{{ $baseClass }}">
            <option value="">— Choisir —</option>
            @foreach(($options ?? []) as $optValue => $optLabel)
                <option value="{{ $optValue }}" {{ (string)$value === (string)$optValue ? 'selected' : '' }}>
                    {{ $optLabel }}
                </option>
            @endforeach
        </select>

    @elseif($type === 'tel')
        {{-- Input téléphone enrichi par intl-tel-input (drapeau pays + validation) --}}
        <input type="tel" id="{{ $id }}" name="{{ $name }}"
               value="{{ $value }}"
               maxlength="{{ $maxlength ?? 20 }}"
               autocomplete="tel"
               placeholder="{{ $placeholder ?: '06 12 34 56 78' }}"
               class="{{ $baseClass }} pdp-tel-input">

    @elseif($type === 'date')
        {{-- Input date natif HTML5 : permet saisie clavier ET sélection au calendrier --}}
        <input type="date" id="{{ $id }}" name="{{ $name }}"
               value="{{ $value }}"
               class="{{ $baseClass }}">

    @elseif($type === 'number')
        <input type="number" id="{{ $id }}" name="{{ $name }}"
               value="{{ $value }}"
               @if($maxlength)maxlength="{{ $maxlength }}"@endif
               placeholder="{{ $placeholder }}"
               class="{{ $baseClass }}">

    @elseif($type === 'email')
        <input type="email" id="{{ $id }}" name="{{ $name }}"
               value="{{ $value }}"
               autocomplete="email"
               placeholder="{{ $placeholder }}"
               class="{{ $baseClass }}">

    @else
        <input type="text" id="{{ $id }}" name="{{ $name }}"
               value="{{ $value }}"
               @if($maxlength)maxlength="{{ $maxlength }}"@endif
               placeholder="{{ $placeholder }}"
               class="{{ $baseClass }}">
    @endif
</div>

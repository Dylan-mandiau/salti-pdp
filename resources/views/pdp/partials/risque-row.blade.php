@php
    /**
     * Une ligne de risque dans le wizard.
     *
     * @var string $key             Clé du risque (ex: 'arrivee_site')
     * @var string $title           Titre court (ex: 'Arrivée sur le site')
     * @var string $description     Description du risque
     * @var array  $risque          Données : ['applicable', 'eu', 'ee']
     * @var bool   $obligatoire     Force l'application (case décochable mais conseillée)
     */
    $applicable = $risque['applicable'] ?? false;
    $eu = $risque['eu'] ?? false;
    $ee = $risque['ee'] ?? false;
@endphp
<div class="border border-gray-200 rounded-lg p-4 bg-white {{ $applicable ? 'border-l-4 border-l-salti-yellow' : '' }}">
    <div class="flex items-start gap-3">
        <input type="checkbox" name="risques.{{ $key }}.applicable" @change="save()"
               {{ $applicable ? 'checked' : '' }}
               class="mt-1 rounded border-gray-300 h-5 w-5">
        <div class="flex-1">
            <div class="flex items-center gap-2 flex-wrap">
                <h4 class="font-medium text-gray-900">{{ $title }}</h4>
                @if($obligatoire ?? false)
                    <span class="text-xs px-1.5 py-0.5 bg-red-100 text-red-700 rounded">Obligatoire</span>
                @endif
            </div>
            @if(!empty($description))
                <p class="text-xs text-gray-500 mt-1">{{ $description }}</p>
            @endif
            <div class="flex gap-4 mt-2 text-sm">
                <label class="flex items-center gap-1.5">
                    <input type="checkbox" name="risques.{{ $key }}.eu" @change="save()"
                           {{ $eu ? 'checked' : '' }}
                           class="rounded border-gray-300">
                    <span>Responsable <strong>EU</strong> (SALTI)</span>
                </label>
                <label class="flex items-center gap-1.5">
                    <input type="checkbox" name="risques.{{ $key }}.ee" @change="save()"
                           {{ $ee ? 'checked' : '' }}
                           class="rounded border-gray-300">
                    <span>Responsable <strong>EE</strong> (Prestataire)</span>
                </label>
            </div>
        </div>
    </div>
</div>

{{--
    Partial réutilisable : ligne d'un document uploadé, vue côté SALTI.
    Variables : $doc (PdpDocument), $pdp (Pdp)
--}}
@php
    $icons = [
        'caces' => '📜', 'autorisation_conduite' => '🚜', 'habilitation' => '⚡',
        'permis_feu' => '🔥', 'fds' => '🧪', 'plan_acces' => '🏢',
        'convention_pret' => '📋', 'autre' => '📄',
    ];
    $typeLabels = [
        'caces' => 'CACES', 'autorisation_conduite' => 'Autorisation de conduite',
        'habilitation' => 'Habilitation', 'permis_feu' => 'Permis feu',
        'fds' => 'FDS', 'plan_acces' => 'Plan d\'accès',
        'convention_pret' => 'Convention de prêt', 'autre' => 'Autre',
    ];
@endphp
<div data-doc-id="{{ $doc->id }}" class="flex items-center justify-between gap-3 p-3 bg-gray-50 border border-gray-200 rounded">
    <div class="flex items-center gap-2 min-w-0 flex-1">
        <span class="text-2xl shrink-0">{{ $icons[$doc->type] ?? '📄' }}</span>
        <div class="min-w-0">
            <div class="text-sm font-medium truncate">{{ $doc->original_filename }}</div>
            <div class="text-xs text-gray-500">
                {{ $typeLabels[$doc->type] ?? 'Autre' }}
                — {{ number_format($doc->size / 1024, 0) }} Ko
                — uploadé par {{ $doc->uploaded_by === 'prestataire' ? 'le prestataire' : 'SALTI' }}
            </div>
        </div>
    </div>
    <div class="flex gap-1.5 shrink-0">
        <a href="{{ route('pdp.download.document', ['pdp' => $pdp, 'doc' => $doc->id]) }}?inline=1" target="_blank"
           class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium px-3 py-1.5 rounded">👁 <span class="hidden sm:inline">Voir</span></a>
        <a href="{{ route('pdp.download.document', ['pdp' => $pdp, 'doc' => $doc->id]) }}"
           class="bg-salti-yellow hover:brightness-95 text-black text-sm font-semibold px-3 py-1.5 rounded">📥</a>
        <button type="button" onclick="saltiDeleteDoc({{ $doc->id }})"
                class="bg-red-50 hover:bg-red-100 text-red-700 text-sm font-medium px-2 py-1.5 rounded border border-red-200">🗑</button>
    </div>
</div>

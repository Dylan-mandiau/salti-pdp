<x-layouts.pdp title="Tableau de bord">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Plans de Prévention</h1>
            <p class="text-sm text-gray-500 mt-1">
                @if(auth()->user()->isQseAdmin())
                    Vue QSE — tous les PDP de toutes les agences
                @else
                    Agence {{ auth()->user()->city ?? auth()->user()->name }}
                @endif
            </p>
        </div>
        <a href="{{ route('pdp.choose-mode') }}"
           class="bg-salti-yellow hover:bg-salti-yellow-dark text-black font-semibold px-5 py-2.5 rounded shadow transition">
            + Nouveau PDP
        </a>
    </div>

    {{-- Stats cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
            <div class="text-sm text-gray-500">Brouillons</div>
            <div class="text-2xl font-bold mt-1">{{ $stats['drafts'] }}</div>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
            <div class="text-sm text-gray-500">En attente prestataire</div>
            <div class="text-2xl font-bold mt-1">{{ $stats['awaiting_prestataire'] }}</div>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
            <div class="text-sm text-gray-500">À valider</div>
            <div class="text-2xl font-bold mt-1 text-orange-600">{{ $stats['awaiting_validation'] }}</div>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
            <div class="text-sm text-gray-500">Signés</div>
            <div class="text-2xl font-bold mt-1 text-green-600">{{ $stats['signed'] }}</div>
        </div>
    </div>

    {{-- Liste --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        @if($pdps->isEmpty())
            <div class="p-12 text-center text-gray-500">
                <p class="mb-4">Aucun plan de prévention pour l'instant.</p>
                <a href="{{ route('pdp.choose-mode') }}" class="text-salti-yellow-dark font-semibold hover:underline">
                    Créer votre premier PDP →
                </a>
            </div>
        @else
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Prestataire</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Opération</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Mode</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Statut</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Modifié</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($pdps as $pdp)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $pdp->prestataire->raison_sociale ?? $pdp->data['ee']['raison_sociale'] ?? '—' }}</div>
                            <div class="text-xs text-gray-500">{{ $pdp->donneur_ordre_nom }}</div>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            {{ \Illuminate\Support\Str::limit($pdp->data['operation']['designation'] ?? '—', 40) }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if($pdp->mode === 'presentiel')
                                <span class="text-blue-700">🏢 Présentiel</span>
                            @else
                                <span class="text-purple-700">🌐 Distance</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $colors = [
                                    'draft' => 'bg-gray-100 text-gray-800',
                                    'awaiting_prestataire' => 'bg-blue-100 text-blue-800',
                                    'awaiting_validation' => 'bg-orange-100 text-orange-800',
                                    'corrections_requested' => 'bg-yellow-100 text-yellow-800',
                                    'awaiting_signatures' => 'bg-purple-100 text-purple-800',
                                    'signed' => 'bg-green-100 text-green-800',
                                    'archived' => 'bg-gray-100 text-gray-600',
                                    'cancelled' => 'bg-red-100 text-red-800',
                                ];
                                $labels = [
                                    'draft' => 'Brouillon',
                                    'awaiting_prestataire' => 'Attente prestataire',
                                    'awaiting_validation' => 'À valider',
                                    'corrections_requested' => 'Corrections demandées',
                                    'awaiting_signatures' => 'À signer',
                                    'signed' => 'Signé',
                                    'archived' => 'Archivé',
                                    'cancelled' => 'Annulé',
                                ];
                            @endphp
                            <span class="inline-block px-2 py-1 text-xs font-medium rounded {{ $colors[$pdp->status] ?? 'bg-gray-100' }}">
                                {{ $labels[$pdp->status] ?? $pdp->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">
                            {{ $pdp->updated_at->diffForHumans() }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('pdp.edit', $pdp) }}"
                                   class="text-salti-yellow-dark hover:underline font-medium text-sm">
                                    Ouvrir
                                </a>

                                @if($pdp->status === 'signed' && auth()->user()->isQseAdmin())
                                    <form method="POST" action="{{ route('pdp.reopen', $pdp) }}" class="inline"
                                          onsubmit="return confirm('Réouvrir ce PDP signé ? Les signatures précédentes seront effacées.');">
                                        @csrf
                                        <button type="submit" class="text-orange-600 hover:underline text-sm">Réouvrir</button>
                                    </form>
                                @endif

                                @if(! in_array($pdp->status, ['signed', 'archived', 'cancelled']))
                                    <form method="POST" action="{{ route('pdp.cancel', $pdp) }}" class="inline"
                                          onsubmit="return confirm('Annuler ce PDP ? Il restera consultable mais marqué annulé.');">
                                        @csrf
                                        <button type="submit" class="text-gray-500 hover:text-red-600 text-sm">Annuler</button>
                                    </form>
                                @endif

                                @if(auth()->user()->isQseAdmin())
                                    <form method="POST" action="{{ route('pdp.destroy', $pdp) }}" class="inline"
                                          onsubmit="return confirm('⚠ Supprimer DÉFINITIVEMENT ce PDP ? Cette action est irréversible.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline text-sm">Supprimer</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

</div>
</x-layouts.pdp>

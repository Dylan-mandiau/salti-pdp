<x-layouts.pdp title="Réglages globaux">
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

    <a href="{{ route('admin.agencies.index') }}" class="text-sm text-gray-500 hover:text-gray-900">← Retour à l'admin</a>
    <h1 class="text-2xl font-bold mt-1 mb-2">Réglages globaux</h1>
    <p class="text-sm text-gray-500 mb-6">
        Fichiers communs à TOUTES les agences. Pour le plan d'accès qui est <strong>spécifique à chaque agence</strong>,
        rendez-vous sur la fiche d'une agence : <a href="{{ route('admin.agencies.index') }}" class="underline">Administration des agences</a>.
    </p>

    @php
        $files = [
            ['permis_feu', 'Permis feu', '🔥', 'Document de permis feu valable pour toutes les agences SALTI.', $permisFeu],
            ['convention_pret', 'Convention de prêt de matériel', '📋', 'Convention type de prêt de matériel applicable partout.', $conventionPret],
        ];
    @endphp

    <div class="space-y-6">
        @foreach($files as [$type, $title, $icon, $desc, $current])
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-start gap-3 mb-3">
                    <div class="text-3xl">{{ $icon }}</div>
                    <div class="flex-1">
                        <h2 class="font-semibold text-gray-900">{{ $title }}</h2>
                        <p class="text-sm text-gray-500">{{ $desc }}</p>
                    </div>
                </div>

                @if($current['path'])
                    <div class="bg-green-50 border border-green-200 rounded p-3 mb-3 flex items-center justify-between">
                        <div class="text-sm">
                            <div class="font-medium text-green-900">📎 {{ $current['filename'] }}</div>
                            <div class="text-xs text-green-700">Fichier actuellement actif</div>
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ route('global-files.download', $type) }}" class="text-sm text-green-800 hover:underline">Télécharger</a>
                            <form method="POST" action="{{ route('admin.settings.delete', $type) }}"
                                  onsubmit="return confirm('Supprimer ce fichier ?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-sm text-red-600 hover:underline">Supprimer</button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="bg-gray-50 border border-gray-200 rounded p-3 mb-3 text-sm text-gray-500">
                        Aucun fichier actif pour le moment.
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.settings.upload', $type) }}" enctype="multipart/form-data" class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center">
                    @csrf
                    <input type="file" name="file" required accept=".pdf,.jpg,.jpeg,.png"
                           class="flex-1 border border-gray-300 rounded px-3 py-2 text-sm">
                    <button type="submit" class="bg-salti-yellow hover:bg-salti-yellow-dark text-black font-semibold px-5 py-2 rounded shadow-sm whitespace-nowrap">
                        {{ $current['path'] ? 'Remplacer' : 'Uploader' }}
                    </button>
                </form>
                <p class="text-xs text-gray-500 mt-2">PDF, JPG, PNG — max 10 Mo.</p>
            </div>
        @endforeach
    </div>
</div>
</x-layouts.pdp>

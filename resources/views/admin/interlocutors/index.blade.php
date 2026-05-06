<x-layouts.pdp title="Interlocuteurs QSE">
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

    <a href="{{ route('admin.agencies.index') }}" class="text-sm text-gray-500 hover:text-gray-900">← Retour à l'admin</a>
    <h1 class="text-2xl font-bold mt-1 mb-2">Interlocuteurs QSE</h1>
    <p class="text-sm text-gray-500 mb-6">
        Liste des contacts sécurité affichés sur la <strong>page 1 du PDP généré</strong> (bloc « Interlocuteurs sécurité »).
        Modifiable à tout moment — les nouveaux PDP utilisent automatiquement la dernière version.
    </p>

    {{-- Formulaire d'ajout --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <h2 class="font-semibold mb-3">+ Ajouter un interlocuteur</h2>
        <form method="POST" action="{{ route('admin.interlocutors.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @csrf
            <input type="text" name="name" placeholder="Nom Prénom" required class="border border-gray-300 rounded px-3 py-2 text-sm">
            <input type="text" name="role" placeholder="Rôle (ex: Coordinateur QSE)" required class="border border-gray-300 rounded px-3 py-2 text-sm">
            <input type="tel" name="phone" placeholder="06.12.34.56.78" maxlength="20" class="pdp-tel-input border border-gray-300 rounded px-3 py-2 text-sm">
            <input type="email" name="email" placeholder="email@salti.fr (optionnel)" class="border border-gray-300 rounded px-3 py-2 text-sm">
            <label class="flex items-center md:col-span-2 text-sm">
                <input type="checkbox" name="is_main" value="1" class="mr-2"> Principal (affiché en gras)
            </label>
            <button type="submit" class="md:col-span-2 bg-salti-yellow hover:bg-salti-yellow-dark text-black font-semibold px-5 py-2 rounded shadow-sm">
                Ajouter
            </button>
        </form>
    </div>

    {{-- Liste --}}
    <div class="space-y-2">
        @forelse($interlocutors as $i)
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <form method="POST" action="{{ route('admin.interlocutors.update', $i) }}" class="grid grid-cols-1 md:grid-cols-[1fr_1fr_140px_1fr_140px_60px] gap-2 md:items-center">
                    @csrf
                    @method('PATCH')
                    <input type="text" name="name" value="{{ $i->name }}" required class="border border-gray-200 rounded px-2 py-1.5 text-sm">
                    <input type="text" name="role" value="{{ $i->role }}" required class="border border-gray-200 rounded px-2 py-1.5 text-sm">
                    <input type="tel" name="phone" value="{{ $i->phone }}" maxlength="20" placeholder="Téléphone" class="border border-gray-200 rounded px-2 py-1.5 text-sm">
                    <input type="email" name="email" value="{{ $i->email }}" placeholder="email" class="border border-gray-200 rounded px-2 py-1.5 text-sm">
                    <label class="flex items-center text-sm gap-1.5"><input type="checkbox" name="is_main" value="1" {{ $i->is_main ? 'checked' : '' }}> Principal</label>
                    <div class="flex gap-1">
                        <button type="submit" class="bg-blue-600 text-white px-2 py-1.5 rounded text-xs hover:bg-blue-700">💾</button>
                </form>
                        <form method="POST" action="{{ route('admin.interlocutors.delete', $i) }}" onsubmit="return confirm('Supprimer {{ $i->name }} ?');" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="bg-red-100 text-red-700 px-2 py-1.5 rounded text-xs hover:bg-red-200">🗑</button>
                        </form>
                    </div>
            </div>
        @empty
            <p class="text-sm text-gray-500 italic text-center py-8">Aucun interlocuteur. Ajoutez le premier ci-dessus.</p>
        @endforelse
    </div>
</div>
</x-layouts.pdp>

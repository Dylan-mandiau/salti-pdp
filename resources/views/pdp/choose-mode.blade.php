<x-layouts.pdp title="Nouveau PDP">
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

    <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Nouveau Plan de Prévention</h1>
        <p class="text-gray-500 mt-2">Comment souhaitez-vous remplir le PDP ?</p>
    </div>

    <form method="POST" action="{{ route('pdp.store') }}" x-data="{ mode: null, donneur: '' }">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">

            {{-- Mode présentiel --}}
            <label class="cursor-pointer" :class="mode === 'presentiel' ? 'ring-4 ring-salti-yellow rounded-xl' : ''">
                <input type="radio" name="mode" value="presentiel" x-model="mode" class="sr-only" required>
                <div class="bg-white rounded-xl shadow-sm border-2 border-gray-200 p-6 hover:border-salti-yellow transition h-full"
                     :class="mode === 'presentiel' ? 'border-salti-yellow' : ''">
                    <div class="text-4xl mb-3">🏢</div>
                    <h2 class="text-lg font-bold mb-2">Présentiel</h2>
                    <p class="text-sm text-gray-600 mb-4">Le prestataire est sur place avec moi, on remplit le PDP ensemble sur cet appareil.</p>
                    <ul class="text-xs text-gray-500 space-y-1">
                        <li>✓ Bascule "vue prestataire" sur la même tablette</li>
                        <li>✓ Signatures sur place au doigt/stylet</li>
                        <li>✓ PDF imprimable immédiatement</li>
                    </ul>
                </div>
            </label>

            {{-- Mode à distance --}}
            <label class="cursor-pointer" :class="mode === 'distance' ? 'ring-4 ring-salti-yellow rounded-xl' : ''">
                <input type="radio" name="mode" value="distance" x-model="mode" class="sr-only" required>
                <div class="bg-white rounded-xl shadow-sm border-2 border-gray-200 p-6 hover:border-salti-yellow transition h-full"
                     :class="mode === 'distance' ? 'border-salti-yellow' : ''">
                    <div class="text-4xl mb-3">🌐</div>
                    <h2 class="text-lg font-bold mb-2">À distance</h2>
                    <p class="text-sm text-gray-600 mb-4">Je remplis ma partie SALTI puis j'envoie un lien sécurisé au prestataire.</p>
                    <ul class="text-xs text-gray-500 space-y-1">
                        <li>✓ Lien magique envoyé par email</li>
                        <li>✓ Le prestataire remplit depuis n'importe quel appareil</li>
                        <li>✓ Validation et signatures différées</li>
                    </ul>
                </div>
            </label>

        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Votre nom (apparaîtra comme donneur d'ordre)</label>
            <input type="text" name="donneur_ordre_nom" x-model="donneur" required
                   placeholder="Prénom NOM"
                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-salti-yellow focus:ring-2 focus:ring-salti-yellow/30 outline-none">
        </div>

        <div class="flex justify-between">
            <a href="{{ route('dashboard') }}" class="text-gray-600 hover:text-gray-900 px-4 py-2">
                ← Annuler
            </a>
            <button type="submit"
                    :disabled="!mode || !donneur"
                    :class="(!mode || !donneur) ? 'opacity-50 cursor-not-allowed' : ''"
                    class="bg-black text-white font-semibold px-6 py-2.5 rounded shadow hover:bg-gray-800 transition">
                Créer le PDP →
            </button>
        </div>
    </form>

</div>
</x-layouts.pdp>

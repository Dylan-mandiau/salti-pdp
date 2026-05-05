<x-layouts.pdp title="Nouvelle agence">
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

    <a href="{{ route('admin.agencies.index') }}" class="text-sm text-gray-500 hover:text-gray-900">← Retour à la liste</a>
    <h1 class="text-2xl font-bold mt-1 mb-6">Créer une nouvelle agence</h1>

    <form method="POST" action="{{ route('admin.agencies.store') }}"
          class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Ville</label>
            <input type="text" name="city" required value="{{ old('city') }}"
                   placeholder="ex. Toulouse"
                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-salti-yellow focus:ring-2 focus:ring-salti-yellow/30 outline-none">
            @error('city')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email d'agence</label>
            <input type="email" name="email" required value="{{ old('email') }}"
                   placeholder="ex. toulouse@salti.fr"
                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-salti-yellow focus:ring-2 focus:ring-salti-yellow/30 outline-none">
            @error('email')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
            <input type="text" name="address" value="{{ old('address') }}"
                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-salti-yellow focus:ring-2 focus:ring-salti-yellow/30 outline-none">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
            <input type="tel" name="phone" value="{{ old('phone') }}" maxlength="20"
                   placeholder="06 12 34 56 78"
                   class="pdp-tel-input w-full border border-gray-300 rounded-md px-3 py-2 focus:border-salti-yellow focus:ring-2 focus:ring-salti-yellow/30 outline-none">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mot de passe initial (optionnel)</label>
            <input type="text" name="password" minlength="8" value="{{ old('password') }}"
                   placeholder="Laissez vide pour générer automatiquement"
                   class="w-full border border-gray-300 rounded-md px-3 py-2 font-mono focus:border-salti-yellow focus:ring-2 focus:ring-salti-yellow/30 outline-none">
            @error('password')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            <p class="text-xs text-gray-500 mt-1">
                Si vide, un mot de passe sécurisé sera généré et affiché une fois pour transmission.
            </p>
        </div>

        <div class="flex justify-between pt-4 border-t border-gray-200">
            <a href="{{ route('admin.agencies.index') }}" class="text-gray-600 hover:text-gray-900 px-4 py-2">
                Annuler
            </a>
            <button type="submit" class="bg-salti-yellow hover:bg-salti-yellow-dark text-black font-semibold px-5 py-2.5 rounded shadow">
                Créer l'agence
            </button>
        </div>
    </form>
</div>
</x-layouts.pdp>

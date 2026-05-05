<x-layouts.pdp title="Éditer agence">
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

    <a href="{{ route('admin.agencies.index') }}" class="text-sm text-gray-500 hover:text-gray-900">← Retour à la liste</a>
    <h1 class="text-2xl font-bold mt-1 mb-6">Éditer {{ $agency->name }}</h1>

    <form method="POST" action="{{ route('admin.agencies.update', $agency) }}"
          class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-4">
        @csrf
        @method('PATCH')

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Ville</label>
            <input type="text" name="city" required value="{{ old('city', $agency->city) }}"
                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-salti-yellow focus:ring-2 focus:ring-salti-yellow/30 outline-none">
            @error('city')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email d'agence</label>
            <input type="email" name="email" required value="{{ old('email', $agency->email) }}"
                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-salti-yellow focus:ring-2 focus:ring-salti-yellow/30 outline-none">
            @error('email')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
            <input type="text" name="address" value="{{ old('address', $agency->address) }}"
                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-salti-yellow focus:ring-2 focus:ring-salti-yellow/30 outline-none">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
            <input type="tel" name="phone" value="{{ old('phone', $agency->phone) }}" maxlength="20"
                   class="pdp-tel-input w-full border border-gray-300 rounded-md px-3 py-2 focus:border-salti-yellow focus:ring-2 focus:ring-salti-yellow/30 outline-none">
        </div>

        <div class="flex items-center pt-2">
            <input type="checkbox" name="require_otp_by_default" value="1" id="otp"
                   {{ $agency->require_otp_by_default ? 'checked' : '' }}
                   class="mr-2 rounded border-gray-300">
            <label for="otp" class="text-sm">
                <strong>Sécurité renforcée par défaut</strong> :
                tous les liens prestataires de cette agence demandent un code OTP par mail/SMS
            </label>
        </div>

        <div class="flex justify-between pt-4 border-t border-gray-200">
            <a href="{{ route('admin.agencies.index') }}" class="text-gray-600 hover:text-gray-900 px-4 py-2">
                Annuler
            </a>
            <button type="submit" class="bg-salti-yellow hover:bg-salti-yellow-dark text-black font-semibold px-5 py-2.5 rounded shadow">
                Enregistrer
            </button>
        </div>
    </form>

    {{-- Bloc reset MDP --}}
    <div class="bg-orange-50 border border-orange-200 rounded-lg p-6 mt-6">
        <h3 class="font-semibold text-orange-900 mb-2">Réinitialiser le mot de passe</h3>
        <p class="text-sm text-orange-800 mb-3">
            Génère un nouveau mot de passe pour <strong>{{ $agency->email }}</strong>. Il sera affiché une seule fois pour que vous puissiez le transmettre.
        </p>
        <form method="POST" action="{{ route('admin.agencies.reset-password', $agency) }}"
              onsubmit="return confirm('Confirmer la réinitialisation du mot de passe ?');"
              class="flex gap-2">
            @csrf
            <input type="text" name="password" minlength="8"
                   placeholder="Mot de passe spécifique (vide = aléatoire)"
                   class="flex-1 border border-orange-300 rounded-md px-3 py-2 font-mono">
            <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white font-semibold px-5 py-2.5 rounded">
                Réinitialiser
            </button>
        </form>
    </div>
</div>
</x-layouts.pdp>

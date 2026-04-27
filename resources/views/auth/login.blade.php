<x-guest-layout>
    <h1 class="text-xl font-bold mb-1 text-black">Connexion</h1>
    <p class="text-sm text-gray-600 mb-6">Espace SALTI / agence</p>

    @if (session('status'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-800 text-sm px-3 py-2 rounded">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-black mb-1">Email d'agence</label>
            <input id="email" name="email" type="email" required autofocus autocomplete="username"
                   value="{{ old('email') }}"
                   placeholder="bordeaux@salti.fr"
                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-salti-yellow focus:ring-2 focus:ring-salti-yellow/40 outline-none">
            @error('email')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mb-4">
            <label for="password" class="block text-sm font-medium text-black mb-1">Mot de passe</label>
            <input id="password" name="password" type="password" required autocomplete="current-password"
                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-salti-yellow focus:ring-2 focus:ring-salti-yellow/40 outline-none">
            @error('password')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="flex items-center justify-between mb-6">
            <label class="flex items-center text-sm text-gray-700">
                <input type="checkbox" name="remember" class="mr-2 rounded border-gray-300">
                Rester connecté
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm text-gray-600 hover:text-black underline">
                    Mot de passe oublié&nbsp;?
                </a>
            @endif
        </div>

        <button type="submit"
                class="w-full bg-salti-yellow hover:bg-salti-yellow-dark text-black font-semibold py-2.5 rounded shadow-sm transition">
            Se connecter
        </button>
    </form>
</x-guest-layout>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'PDP SALTI' }}</title>

    {{-- Tailwind via CDN : aucun build npm requis, déploiement = git pull + composer install --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Charte SALTI : jaune #FFDD00 sur fond blanc, texte noir
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'salti-yellow': '#FFDD00',        // primaire
                        'salti-yellow-dark': '#E6C700',   // hover / actif
                    }
                }
            }
        }
    </script>
    {{-- Alpine.js pour l'interactivité légère --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- signature_pad pour la capture de signature --}}
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.2.0/dist/signature_pad.umd.min.js"></script>

    {{-- intl-tel-input pour les champs téléphone (drapeau France par défaut, validation, formatage) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.12/build/css/intlTelInput.min.css">
    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.12/build/js/intlTelInput.min.js"></script>

    <style>
        /* Charte SALTI : jaune #FFDD00 / blanc / noir */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #FFFFFF;
            color: #000000;
        }
        /* Force la largeur 100% pour intl-tel-input wrapper */
        .iti { width: 100%; }
        .iti__country-list { z-index: 50; }
    </style>
</head>
<body class="min-h-screen bg-white text-black">

    @auth
    <nav class="bg-black text-white shadow" x-data="{ menuOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-14 items-center">
                {{-- Logo + brand (toujours visible) --}}
                <a href="{{ route('dashboard') }}" class="flex items-center shrink-0">
                    <span class="bg-salti-yellow text-black font-bold px-2.5 py-1 rounded text-sm">SALTI</span>
                    <span class="ml-2 text-sm font-medium hidden sm:inline">Plan de Prévention</span>
                </a>

                {{-- Liens desktop (md+) --}}
                <div class="hidden md:flex items-center space-x-5 flex-1 ml-8">
                    <a href="{{ route('dashboard') }}" class="text-sm hover:text-salti-yellow">Tableau de bord</a>
                    <a href="{{ route('pdp.choose-mode') }}" class="text-sm hover:text-salti-yellow">+ Nouveau PDP</a>
                    @if(auth()->user()->isQseAdmin())
                        <a href="{{ route('admin.agencies.index') }}" class="text-sm hover:text-salti-yellow">⚙ Administration</a>
                        <a href="{{ route('pdp.calibration') }}" target="_blank" class="text-sm hover:text-salti-yellow">📐 Calibration</a>
                    @endif
                </div>

                {{-- Bloc utilisateur desktop --}}
                <div class="hidden md:flex items-center space-x-4">
                    <span class="text-sm text-gray-300">
                        {{ auth()->user()->name }}
                        @if(auth()->user()->isQseAdmin())
                            <span class="ml-1 px-2 py-0.5 bg-salti-yellow text-black text-xs rounded">QSE</span>
                        @endif
                    </span>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-sm hover:text-salti-yellow">Déconnexion</button>
                    </form>
                </div>

                {{-- Bouton burger mobile --}}
                <button type="button" @click="menuOpen = !menuOpen" class="md:hidden p-2 -mr-2 rounded hover:bg-gray-800">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path x-show="!menuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        <path x-show="menuOpen" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Menu mobile déroulant --}}
            <div x-show="menuOpen" x-cloak class="md:hidden pb-3 space-y-1 border-t border-gray-800 pt-2">
                <a href="{{ route('dashboard') }}" class="block py-2 text-sm hover:text-salti-yellow">📋 Tableau de bord</a>
                <a href="{{ route('pdp.choose-mode') }}" class="block py-2 text-sm hover:text-salti-yellow">➕ Nouveau PDP</a>
                @if(auth()->user()->isQseAdmin())
                    <a href="{{ route('admin.agencies.index') }}" class="block py-2 text-sm hover:text-salti-yellow">⚙ Administration</a>
                    <a href="{{ route('pdp.calibration') }}" target="_blank" class="block py-2 text-sm hover:text-salti-yellow">📐 Calibration</a>
                @endif
                <div class="border-t border-gray-800 pt-2 mt-2">
                    <div class="text-xs text-gray-400 py-1">
                        {{ auth()->user()->name }}
                        @if(auth()->user()->isQseAdmin())<span class="ml-1 px-1.5 py-0.5 bg-salti-yellow text-black text-xs rounded">QSE</span>@endif
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block py-2 text-sm hover:text-salti-yellow text-left">🚪 Déconnexion</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>
    @endauth

    @if(session('success'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded whitespace-pre-line">
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">
                {{ session('error') }}
            </div>
        </div>
    @endif

    <main class="py-6">
        {{ $slot }}
    </main>

    {{-- Initialisation globale des champs téléphone (drapeau France par défaut) --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // intl-tel-input sur tous les inputs .pdp-tel-input
            document.querySelectorAll('.pdp-tel-input').forEach(el => {
                const iti = window.intlTelInput(el, {
                    initialCountry: 'fr',
                    preferredCountries: ['fr', 'be', 'ch', 'lu', 'es', 'pt', 'it', 'de', 'gb'],
                    nationalMode: true,                          // affichage au format national
                    autoPlaceholder: 'aggressive',
                    formatOnDisplay: true,
                    utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.12/build/js/utils.js',
                });

                // Filtre la saisie : autorise uniquement chiffres + espaces + + ( ) . -
                el.addEventListener('input', () => {
                    el.value = el.value.replace(/[^\d\s+()\-.]/g, '');
                });

                // Stocke l'instance pour pouvoir lire la valeur formatée si besoin
                el._iti = iti;
            });
        });
    </script>

</body>
</html>

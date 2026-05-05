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
    <nav class="bg-black text-white shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-14">
                <div class="flex items-center space-x-6">
                    <a href="{{ route('dashboard') }}" class="flex items-center">
                        <span class="bg-salti-yellow text-black font-bold px-3 py-1 rounded">SALTI</span>
                        <span class="ml-2 text-sm font-medium">Plan de Prévention</span>
                    </a>
                    <a href="{{ route('dashboard') }}" class="text-sm hover:text-salti-yellow">Tableau de bord</a>
                    <a href="{{ route('pdp.choose-mode') }}" class="text-sm hover:text-salti-yellow">+ Nouveau PDP</a>
                    @if(auth()->user()->isQseAdmin())
                        <a href="{{ route('admin.agencies.index') }}" class="text-sm hover:text-salti-yellow">⚙ Administration</a>
                    @endif
                </div>
                <div class="flex items-center space-x-4">
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

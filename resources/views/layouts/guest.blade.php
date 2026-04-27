<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'PDP SALTI') }}</title>

    {{-- Tailwind via CDN — pas de build npm --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'salti-yellow': '#FFDD00',
                        'salti-yellow-dark': '#E6C700',
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #FFFFFF;
            color: #000000;
        }
    </style>
</head>
<body class="min-h-screen">
    <div class="min-h-screen flex flex-col items-center justify-center px-4 py-12">

        <div class="mb-8 text-center">
            <div class="inline-block bg-salti-yellow text-black font-bold text-3xl px-6 py-3 rounded">SALTI</div>
            <p class="mt-3 text-sm text-gray-700">Plan de Prévention 2026</p>
        </div>

        <div class="w-full max-w-md bg-white border border-gray-200 rounded-lg shadow-sm px-6 py-6">
            {{ $slot }}
        </div>

        <p class="mt-6 text-xs text-gray-500">
            © {{ date('Y') }} SALTI · Application interne
        </p>
    </div>
</body>
</html>

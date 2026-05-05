<x-layouts.pdp title="Administration - Agences">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold">Administration des comptes</h1>
            <p class="text-sm text-gray-500 mt-1">
                Compte QSE central + agences SALTI. Réservé au service QSE.
            </p>
        </div>
        <a href="{{ route('admin.agencies.create') }}"
           class="bg-salti-yellow hover:bg-salti-yellow-dark text-black font-semibold px-5 py-2.5 rounded shadow transition">
            + Nouvelle agence
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Compte</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Email</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Ville</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">PDP créés</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">OTP par défaut</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($agencies as $a)
                <tr class="hover:bg-gray-50 {{ $a->isQseAdmin() ? 'bg-salti-yellow/5' : '' }}">
                    <td class="px-4 py-3">
                        <div class="font-medium">{{ $a->name }}</div>
                        @if($a->isQseAdmin())
                            <span class="inline-block mt-1 px-2 py-0.5 bg-salti-yellow text-black text-xs rounded font-semibold">QSE</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm font-mono">{{ $a->email }}</td>
                    <td class="px-4 py-3 text-sm">{{ $a->city ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm">{{ $a->pdps_count }}</td>
                    <td class="px-4 py-3 text-sm">
                        @if($a->require_otp_by_default)
                            <span class="text-orange-700">🔒 Activé</span>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex justify-end gap-2 items-center">
                            @if(! $a->isQseAdmin())
                                <a href="{{ route('admin.agencies.edit', $a) }}" class="text-sm text-blue-600 hover:underline">Éditer</a>
                            @endif
                            <form method="POST" action="{{ route('admin.agencies.reset-password', $a) }}" class="inline"
                                  onsubmit="return confirm('Réinitialiser le mot de passe de {{ $a->email }} ?');">
                                @csrf
                                <button type="submit" class="text-sm text-orange-600 hover:underline">Reset MDP</button>
                            </form>
                            @if(! $a->isQseAdmin())
                                <form method="POST" action="{{ route('admin.agencies.destroy', $a) }}" class="inline"
                                      onsubmit="return confirm('⚠ Supprimer DÉFINITIVEMENT {{ $a->email }} ? Cela supprimera aussi tous les PDP de cette agence.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm text-red-600 hover:underline">Supprimer</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <p class="text-xs text-gray-500 mt-4">
        🔒 <strong>Reset MDP</strong> : génère un nouveau mot de passe aléatoire qui s'affiche une seule fois.
        À transmettre à l'agence par un canal sécurisé (téléphone, sms, etc.).
    </p>
</div>
</x-layouts.pdp>

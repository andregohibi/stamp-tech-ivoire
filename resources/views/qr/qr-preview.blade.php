@extends('layouts.app')

@section('content')
<div class="p-6">
    <div class="max-w-5xl mx-auto bg-white dark:bg-[#0f1720] rounded-lg shadow-md overflow-hidden">
        <div class="md:flex">
            {{-- Left: QR image & actions --}}
            <div class="md:w-1/2 p-8 flex flex-col items-center justify-center bg-gray-50 dark:bg-[#071019]">
                @if($qr->qr_image_path && Storage::disk('public')->exists($qr->qr_image_path))
                    <img src="{{ Storage::disk('public')->url($qr->qr_image_path) }}" alt="QR Code" class="w-64 h-64 p-2 bg-white rounded-lg border">
                @else
                    <div class="w-64 h-64 flex items-center justify-center bg-gray-100 rounded-lg text-gray-500">Pas d'image</div>
                @endif

                <div class="mt-6 flex gap-3">
                    @if($qr->signatory)
                        <a href="{{ route('signatories.qr.download', $qr->signatory) }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Télécharger</a>
                    @endif
                    @if($qr->qr_image_path)
                        <a href="{{ Storage::disk('public')->url($qr->qr_image_path) }}" target="_blank" class="px-4 py-2 bg-gray-200 dark:bg-gray-800 text-gray-800 dark:text-gray-200 rounded">Ouvrir image</a>
                    @endif
                    <button id="copyCode" class="px-4 py-2 bg-green-600 text-white rounded">Copier le code</button>
                </div>

                <p class="mt-3 text-sm text-gray-500">QR généré par : <span class="font-medium text-gray-800 dark:text-gray-100">{{ $qr->metadata['generated_by_user'] ?? 'N/A' }}</span></p>
            </div>

            {{-- Right: details --}}
            <div class="md:w-1/2 p-8">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Informations du QR</h2>

                <div class="mt-4 grid grid-cols-1 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Code Unique</p>
                        <div class="flex items-center gap-3">
                            <code class="bg-gray-100 dark:bg-[#081019] px-3 py-2 rounded font-mono">{{ $qr->unique_code }}</code>
                            <span class="text-sm text-gray-400">({{ strlen($qr->unique_code) }} caractères)</span>
                        </div>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Statut</p>
                        @php
                            $status = $qr->status ?? 'unknown';
                            $badgeClasses = match($status) {
                                'active' => 'bg-green-100 text-green-800',
                                'revoked' => 'bg-red-100 text-red-800',
                                'expired' => 'bg-yellow-100 text-yellow-800',
                                default => 'bg-gray-100 text-gray-800',
                            };
                        @endphp
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $badgeClasses }}">{{ ucfirst($status === 'active' ? 'Actif' : $status) }}</span>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Signataire</p>
                        <p class="text-base font-medium text-gray-900 dark:text-gray-100">{{ $qr->signatory?->full_name ?? 'N/A' }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Entreprise</p>
                        <p class="text-base font-medium text-gray-900 dark:text-gray-100">{{ $qr->company?->name ?? 'N/A' }}</p>
                    </div>

                    <div class="pt-4 border-t">
                        <p class="text-sm font-medium text-gray-500 mb-3">Informations temporelles</p>
                        <div class="grid grid-cols-1 gap-3">
                            <div>
                                <p class="text-xs text-gray-600">Date d'émission</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $qr->issued_at?->format('d/m/Y H:i') ?? 'N/A' }}</p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-600">Date d'expiration</p>
                                <p class="text-sm font-medium {{ ($qr->expires_at && now()->isAfter($qr->expires_at)) ? 'text-red-600' : (($qr->expires_at && now()->diffInDays($qr->expires_at) <= 7) ? 'text-yellow-600' : 'text-gray-900') }} dark:text-gray-100">{{ $qr->expires_at?->format('d/m/Y H:i') ?? 'Jamais' }}</p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-600">Dernière vérification</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $qr->last_verified_at?->format('d/m/Y H:i') ?? 'Jamais vérifiée' }}</p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-600">Nombre de vérifications</p>
                                <p class="text-sm font-bold text-blue-600">{{ $qr->verification_count ?? 0 }}</p>
                            </div>
                        </div>
                    </div>

                    @if($qr->status === 'revoked')
                        <div class="pt-4 bg-red-50 p-4 rounded">
                            <p class="text-sm font-medium text-red-800 mb-2">Raison de la révocation</p>
                            <p class="text-base text-red-700">{{ $qr->revocation_reason ?? 'Aucune raison spécifiée' }}</p>
                            <p class="text-xs text-red-600 mt-2">Révoqué le {{ $qr->revoked_at?->format('d/m/Y H:i') ?? 'N/A' }}</p>
                        </div>
                    @endif

                    @if($qr->metadata)
                        <div class="pt-4">
                            <p class="text-sm font-medium text-gray-500 mb-2">Métadonnées</p>
                            <pre class="p-3 rounded bg-gray-100 dark:bg-[#07101a] text-xs overflow-auto">{{ json_encode($qr->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    @endif

                    <div class="pt-4 flex gap-3">
                        <a href="{{ url()->previous() }}" class="px-4 py-2 bg-gray-800 text-white rounded">Retour</a>
                        <a href="{{ route('qr.preview', $qr) }}" class="px-4 py-2 border border-gray-300 rounded">Ouvrir preview dédiée</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const btn = document.getElementById('copyCode');
            if (!btn) return;
            btn.addEventListener('click', async function () {
                try {
                    await navigator.clipboard.writeText('{{ $qr->unique_code }}');
                    btn.textContent = 'Copié';
                    setTimeout(() => btn.textContent = 'Copier le code', 2000);
                } catch (e) {
                    alert('Impossible de copier le code');
                }
            });
        });
    </script>

@endsection

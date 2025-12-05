@php
/**
 * Variables disponibles dans la vue:
 * - $valid (bool)
 * - $message (string)
 * - $data (array) [si valid=true]
 */
@endphp

<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vérification QR</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-900">
<div class="max-w-3xl mx-auto p-6">
    <div class="bg-white shadow rounded-lg p-6">
        <h1 class="text-2xl font-bold mb-4">Résultat de la vérification</h1>

        @if(!empty($valid) && $valid === true)
            <div class="p-4 bg-green-50 border border-green-200 rounded mb-4">
                <strong class="text-green-700">Valide</strong>
                <p class="text-sm">{{ $message ?? 'QR code valide et authentique.' }}</p>
            </div>

            <h2 class="text-lg font-semibold mt-4">Signataire</h2>
            <div class="mt-2 mb-4">
                <p><strong>Nom :</strong> {{ $data['signatory']['full_name'] ?? '-' }}</p>
                <p><strong>Poste :</strong> {{ $data['signatory']['position'] ?? '-' }}</p>
                <p><strong>Email :</strong> {{ $data['signatory']['email'] ?? '-' }}</p>
                <p><strong>Téléphone :</strong> {{ $data['signatory']['phone'] ?? '-' }}</p>
                @if(!empty($data['signatory']['signature_image_url']))
                    <p class="mt-2"><strong>Signature :</strong></p>
                    <img src="{{ $data['signatory']['signature_image_url'] }}" alt="Signature" class="max-h-40 mt-2">
                @endif
            </div>

            <h2 class="text-lg font-semibold mt-4">Entreprise</h2>
            <div class="mt-2 mb-4">
                <p><strong>Nom :</strong> {{ $data['company']['name'] ?? '-' }}</p>
                <p><strong>RCCM :</strong> {{ $data['company']['registration_number'] ?? '-' }}</p>
            </div>

            <h2 class="text-lg font-semibold mt-4">Informations QR</h2>
            <div class="mt-2">
                <p><strong>Code unique :</strong> {{ $data['qr_info']['unique_code'] ?? '-' }}</p>
                <p><strong>Statut :</strong> {{ $data['qr_info']['status'] ?? '-' }}</p>
                <p><strong>Émis le :</strong> {{ $data['qr_info']['issued_at'] ?? '-' }}</p>
                <p><strong>Expire le :</strong> {{ $data['qr_info']['expires_at'] ?? '-' }}</p>
            </div>

        @else
            <div class="p-4 bg-red-50 border border-red-200 rounded">
                <strong class="text-red-700">Non valide</strong>
                <p class="text-sm">{{ $message ?? 'QR code invalide.' }}</p>
            </div>
        @endif

        <div class="mt-6">
            <a href="/" class="text-sm text-blue-600 hover:underline">Retour</a>
        </div>
    </div>
</div>
</body>
</html>

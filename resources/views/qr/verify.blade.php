<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification QR Code</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen py-8 px-4">
    <div class="max-w-4xl mx-auto">
        
        @if($status === 'active')
            {{-- QR Code Valide --}}
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                {{-- Header avec badge de succès --}}
                <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-8 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="flex items-center gap-3 mb-2">
                                <i class="fas fa-check-circle text-4xl"></i>
                                <h1 class="text-3xl font-bold">QR Code Valide</h1>
                            </div>
                            <p class="text-green-100 text-lg">Signature authentifiée</p>
                        </div>
                        <div class="text-center bg-white/20 backdrop-blur-sm rounded-lg px-6 py-4">
                            <p class="text-sm text-green-100 mb-1">Nombre de scans</p>
                            <p class="text-3xl font-bold">{{ $scanCount ?? 0 }}</p>
                        </div>
                    </div>
                </div>

                <div class="p-8 space-y-8">
                    {{-- Informations du signataire (depuis le payload décrypté) --}}
                    <div class="border-l-4 border-green-500 pl-6">
                        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                            <i class="fas fa-user-check text-green-600"></i>
                            Informations du Signataire
                        </h2>
                        <div class="grid md:grid-cols-2 gap-6">
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-sm text-gray-600 mb-1">Nom complet</p>
                                <p class="text-lg font-semibold text-gray-900">{{ $signatory['full_name'] ?? 'Non spécifié' }}</p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-sm text-gray-600 mb-1">Fonction</p>
                                <p class="text-lg font-semibold text-gray-900">{{ $signatory['position'] ?? 'Non spécifié' }}</p>
                            </div>
                            @if(isset($signatory['department']) && !empty($signatory['department']))
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-sm text-gray-600 mb-1">Département</p>
                                <p class="text-lg font-semibold text-gray-900">{{ $signatory['department'] }}</p>
                            </div>
                            @endif
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-sm text-gray-600 mb-1">Email</p>
                                <p class="text-lg font-semibold text-gray-900">{{ $signatory['email'] ?? 'Non spécifié' }}</p>
                            </div>
                            @if(isset($signatory['phone']) && !empty($signatory['phone']))
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-sm text-gray-600 mb-1">Téléphone</p>
                                <p class="text-lg font-semibold text-gray-900">{{ $signatory['phone'] }}</p>
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Informations de l'entreprise (depuis le payload décrypté) --}}
                    <div class="border-l-4 border-blue-500 pl-6">
                        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                            <i class="fas fa-building text-blue-600"></i>
                            Entreprise
                        </h2>
                        <div class="bg-blue-50 rounded-lg p-6">
                            <p class="text-2xl font-bold text-blue-900 mb-2">{{ $company['name'] ?? 'Non spécifié' }}</p>
                            @if(isset($company['registration_number']) && !empty($company['registration_number']))
                                <p class="text-gray-700 flex items-start gap-2 mt-3">
                                    <i class="fas fa-id-card text-blue-600 mt-1"></i>
                                    <span><strong>N° d'enregistrement :</strong> {{ $company['registration_number'] }}</span>
                                </p>
                            @endif
                        </div>
                    </div>

                    {{-- Signature image si disponible dans le payload --}}
                    @if(isset($signatory['signature_image']) && !empty($signatory['signature_image']))
                        <div class="border-l-4 border-purple-500 pl-6">
                            <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                                <i class="fas fa-signature text-purple-600"></i>
                                Signature
                            </h2>
                            <div class="bg-purple-50 rounded-lg p-6 flex justify-center">
                                <img src="{{ $signatory['signature_image'] }}" 
                                     alt="Signature" 
                                     class="max-h-32 border-2 border-purple-200 rounded-lg">
                            </div>
                        </div>
                    @endif

                    {{-- Détails de vérification --}}
                    <div class="border-l-4 border-gray-500 pl-6">
                        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                            <i class="fas fa-info-circle text-gray-600"></i>
                            Détails de Vérification
                        </h2>
                        <div class="grid md:grid-cols-2 gap-6">
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-sm text-gray-600 mb-1">Date d'émission</p>
                                <p class="text-lg font-semibold text-gray-900">
                                    @if(isset($issuedAt))
                                        {{ \Carbon\Carbon::parse($issuedAt)->format('d/m/Y à H:i') }}
                                    @elseif(isset($qrStamp->issued_at))
                                        {{ $qrStamp->issued_at->format('d/m/Y à H:i') }}
                                    @else
                                        Non disponible
                                    @endif
                                </p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-sm text-gray-600 mb-1">Date d'expiration</p>
                                <p class="text-lg font-semibold text-gray-900">
                                    {{ $qrStamp->expires_at ? $qrStamp->expires_at->format('d/m/Y à H:i') : 'Aucune' }}
                                </p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-sm text-gray-600 mb-1">Créé par</p>
                                <p class="text-lg font-semibold text-gray-900">
                                    {{ $qrStamp->metadata['generated_by_user'] ?? $qrStamp->created_by ?? 'Système' }}
                                </p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-sm text-gray-600 mb-1">IP de génération</p>
                                <p class="text-sm font-mono text-gray-900">
                                    {{ $qrStamp->metadata['ip_address'] ?? 'Non disponible' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Métadonnées techniques --}}
                    @if(isset($qrStamp->metadata['user_agent']) || isset($qrStamp->metadata['notes']))
                    <div class="border-l-4 border-indigo-500 pl-6">
                        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                            <i class="fas fa-database text-indigo-600"></i>
                            Métadonnées Techniques
                        </h2>
                        <div class="space-y-4">
                            @if(isset($qrStamp->metadata['user_agent']) && !empty($qrStamp->metadata['user_agent']))
                            <div class="bg-indigo-50 rounded-lg p-4">
                                <p class="text-sm text-gray-600 mb-2 flex items-center gap-2">
                                    <i class="fas fa-desktop text-indigo-600"></i>
                                    Navigateur de génération
                                </p>
                                <p class="text-sm font-mono text-gray-800 break-all">
                                    {{ $qrStamp->metadata['user_agent'] }}
                                </p>
                            </div>
                            @endif

                            @if(isset($qrStamp->metadata['notes']) && !empty($qrStamp->metadata['notes']))
                            <div class="bg-yellow-50 rounded-lg p-4">
                                <p class="text-sm text-gray-600 mb-2 flex items-center gap-2">
                                    <i class="fas fa-sticky-note text-yellow-600"></i>
                                    Notes
                                </p>
                                <p class="text-gray-800">{{ $qrStamp->metadata['notes'] }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    {{-- Badge de sécurité --}}
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-200 rounded-lg p-6">
                        <div class="flex items-center gap-4">
                            <div class="bg-green-500 text-white rounded-full p-4">
                                <i class="fas fa-shield-check text-3xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-800 mb-1">Certificat Authentique</h3>
                                <p class="text-gray-600">
                                    Ce document a été vérifié cryptographiquement et contient les informations 
                                    d'origine encodées au moment de sa création.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        @elseif($status === 'expired')
            {{-- QR Code Expiré --}}
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-orange-500 to-red-500 p-8 text-white">
                    <div class="flex items-center gap-3 mb-2">
                        <i class="fas fa-clock text-5xl"></i>
                        <div>
                            <h1 class="text-3xl font-bold">QR Code Expiré</h1>
                            <p class="text-orange-100 text-lg">Ce code n'est plus valide</p>
                        </div>
                    </div>
                </div>
                <div class="p-8">
                    <div class="bg-orange-50 border-l-4 border-orange-500 p-6 rounded-lg">
                        <p class="text-lg text-gray-800 mb-4">{{ $message }}</p>
                        @if(isset($expiredAt))
                            <p class="text-gray-600 flex items-center gap-2">
                                <i class="fas fa-calendar-times text-orange-600"></i>
                                Date d'expiration : {{ $expiredAt->format('d/m/Y à H:i') }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>

        @elseif($status === 'revoked')
            {{-- QR Code Révoqué --}}
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-red-600 to-red-700 p-8 text-white">
                    <div class="flex items-center gap-3 mb-2">
                        <i class="fas fa-ban text-5xl"></i>
                        <div>
                            <h1 class="text-3xl font-bold">QR Code Révoqué</h1>
                            <p class="text-red-100 text-lg">Ce code a été désactivé</p>
                        </div>
                    </div>
                </div>
                <div class="p-8">
                    <div class="bg-red-50 border-l-4 border-red-600 p-6 rounded-lg">
                        <p class="text-lg text-gray-800 mb-4">{{ $message }}</p>
                        @if(isset($revokedAt))
                            <p class="text-gray-600 flex items-center gap-2">
                                <i class="fas fa-calendar-times text-red-600"></i>
                                Date de révocation : {{ $revokedAt->format('d/m/Y à H:i') }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>

        @elseif($status === 'inactive')
            {{-- QR Code Inactif --}}
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-gray-500 to-gray-600 p-8 text-white">
                    <div class="flex items-center gap-3 mb-2">
                        <i class="fas fa-pause-circle text-5xl"></i>
                        <div>
                            <h1 class="text-3xl font-bold">QR Code Inactif</h1>
                            <p class="text-gray-100 text-lg">Ce code n'est pas activé</p>
                        </div>
                    </div>
                </div>
                <div class="p-8">
                    <div class="bg-gray-50 border-l-4 border-gray-500 p-6 rounded-lg">
                        <p class="text-lg text-gray-800">{{ $message }}</p>
                    </div>
                </div>
            </div>

        @elseif($status === 'invalid')
            {{-- QR Code Invalide --}}
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-red-500 to-pink-600 p-8 text-white">
                    <div class="flex items-center gap-3 mb-2">
                        <i class="fas fa-exclamation-triangle text-5xl"></i>
                        <div>
                            <h1 class="text-3xl font-bold">QR Code Invalide</h1>
                            <p class="text-red-100 text-lg">Ce code n'est pas valide</p>
                        </div>
                    </div>
                </div>
                <div class="p-8">
                    <div class="bg-red-50 border-l-4 border-red-500 p-6 rounded-lg">
                        <p class="text-lg text-gray-800 mb-4">{{ $message }}</p>
                        @if(isset($security_alert) && $security_alert)
                            <div class="mt-4 bg-red-100 border border-red-300 rounded-lg p-4">
                                <p class="text-red-800 font-semibold flex items-center gap-2">
                                    <i class="fas fa-shield-alt"></i>
                                    Alerte de sécurité : Cette tentative a été enregistrée.
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        @elseif($status === 'not_found')
            {{-- QR Code Non Trouvé --}}
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-yellow-500 to-orange-500 p-8 text-white">
                    <div class="flex items-center gap-3 mb-2">
                        <i class="fas fa-search text-5xl"></i>
                        <div>
                            <h1 class="text-3xl font-bold">QR Code Introuvable</h1>
                            <p class="text-yellow-100 text-lg">Code non reconnu</p>
                        </div>
                    </div>
                </div>
                <div class="p-8">
                    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-6 rounded-lg">
                        <p class="text-lg text-gray-800">{{ $message }}</p>
                        <p class="text-gray-600 mt-3">Veuillez vérifier que le lien est correct ou contactez l'émetteur du QR code.</p>
                    </div>
                </div>
            </div>

        @else
            {{-- Erreur ou statut inconnu --}}
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-gray-700 to-gray-800 p-8 text-white">
                    <div class="flex items-center gap-3 mb-2">
                        <i class="fas fa-times-circle text-5xl"></i>
                        <div>
                            <h1 class="text-3xl font-bold">Erreur de Vérification</h1>
                            <p class="text-gray-200 text-lg">Une erreur est survenue</p>
                        </div>
                    </div>
                </div>
                <div class="p-8">
                    <div class="bg-gray-50 border-l-4 border-gray-700 p-6 rounded-lg">
                        <p class="text-lg text-gray-800">{{ $message }}</p>
                        <p class="text-gray-600 mt-3">Veuillez réessayer ultérieurement ou contacter le support technique.</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Footer --}}
        <div class="mt-8 text-center">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <p class="text-gray-600 flex items-center justify-center gap-2">
                    <i class="fas fa-shield-alt text-green-600"></i>
                    Vérification sécurisée effectuée le {{ now()->format('d/m/Y à H:i') }}
                </p>
                <p class="text-sm text-gray-500 mt-2">
                    Les informations affichées proviennent du payload crypté original du QR code
                </p>
            </div>
        </div>

    </div>
</body>
</html>
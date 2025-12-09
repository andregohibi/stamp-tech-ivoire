<?php

namespace App\Http\Controllers;

use Log;
use App\Models\QrStamp;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Crypt;

class QrVerificationController extends Controller
{
    private function decryptPayload(string $encryptedPayload)
    {
       try{
           $decrypted=Crypt::decryptString($encryptedPayload);
           return json_decode($decrypted, true);
       } catch (\Exception $e) {
           throw new \Exception("Une erreur est survenue lors de la décryption du payload.");
       }
    }
    

/**
 * Vérifie si le token fourni correspond au hash stocké en base de données
 *
 * @param string $token Le token unique du QR code
 * @param string $storedHash Le hash du token stocké en base de données
 * @return bool True si le token correspond au hash, false sinon
 */
    private function verifyTokenHash(string $token, string $storedHash): bool
    {
        $generatedHash = hash('sha256', $token);
        return hash_equals($storedHash, $generatedHash);        
    }

/**
 * Log an attempt to verify a QR code as suspicious
 *
 * @param QrStamp $qrStamp The QR code being verified
 * @param Request $request The request being logged
 */
        private function logSuspiciousAttempt(QrStamp $qrStamp, Request $request): void
        {
            $qrStamp->update([
                'verification_attempts' => $qrStamp->verification_attempts + 1,
                'last_suspicious_attempt' => now(),
                'last_suspicious_ip' => $request->ip(),
                'last_suspicious_user_agent' => $request->userAgent(),
            ]);
        }

     /**
     * Vérifie et affiche les informations du QR code
     *
     * @param string $token Le token unique du QR code
     * @return \Illuminate\View\View
     */

    public function verify($token , Request $request)
    {
        
        try{

             // Première vérification : recherche par unique_code
            $qrStamp = QrStamp::where("unique_code", $token)
                ->with(['signatory', 'company'])
                ->first();

            // Si le QR code n'existe pas du tout
            if (!$qrStamp) {
                // Log la tentative avec un token inexistant (optionnel)
                Log::warning('Tentative de vérification avec un token inexistant', [
                    'token' => substr($token, 0, 10) . '...', // Partiel pour la sécurité
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'timestamp' => now()
                ]);

                return view('qr.verify', [
                    'status' => 'not_found',
                    'message' => 'QR code introuvable. Veuillez vérifier le lien.',
                    'qrStamp' => null
                ]);
            }

            // Deuxième vérification : validation du hash du token
            if (!$this->verifyTokenHash($token, $qrStamp->token_hash)) {
                // Le token existe mais son hash ne correspond pas = tentative de falsification
                $this->logSuspiciousAttempt($qrStamp, $request);
                
                return view('qr.verify', [
                    'status' => 'invalid',
                    'message' => 'QR Code invalide. Le code de vérification ne correspond pas.',
                    'qrStamp' => null,
                    'security_alert' => true
                ]);
            }
           


            // Incrémenter le compteur de scans
            $qrStamp->incrementVerification();

            // Décrypter le payload
            $payload = $this->decryptPayload($qrStamp->payload_encrypted);

            //verfier le status du qr code
            switch($qrStamp->status){

                case 'active':
                    //verifier la date d'expiration
                    if ($qrStamp->expires_at && now()->isAfter($qrStamp->expires_at)) {
                        $qrStamp->update(['status' => 'expired']);
                        return view('qr.verify', [
                           'status' => 'expired',
                            'message' => 'Ce QR Code a expiré le ' . $qrStamp->expires_at->format('d/m/Y'),
                            'qrStamp' => $qrStamp,
                            'payload' => $payload,
                            'expiredAt' => $qrStamp->expires_at
                        ]);
                    }

                    return view('qr.verify', [
                       'status' => 'active',
                        'message' => 'QR Code valide et actif',
                        'qrStamp' => $qrStamp,
                        'signatory' => $payload, 
                        'company' => $payload['company'] ?? null, 
                        'payload' => $payload,
                        'scanCount' => $qrStamp->verification_count,
                        'issuedAt' => $payload['issued_at'] ?? null
                    ]);

                case 'expired':
                    return view('qr.verify',[
                        'status' => 'expired',
                        'message' => 'Ce QR Code a expiré le ' . $qrStamp->expires_at->format('d/m/Y'),
                        'qrStamp' => $qrStamp,
                        'payload' => $payload,
                        'expiredAt' => $qrStamp->expires_at
                    ]);

                case 'revoked':
                    return view('qr.verify',[
                        'status' => 'revoked',
                        'message' => 'Ce QR Code a été révoqué. Raison : ' . $qrStamp->revocation_reason,
                        'qrStamp' => $qrStamp,
                        'payload' => $payload,
                        'revokedAt' => $qrStamp->revoked_at
                    ]);

                case 'inactive':
                    return view('qr.verify',[
                       'status' => 'inactive',
                        'message' => 'Ce QR Code est inactif.',
                        'qrStamp' => $qrStamp,
                        'payload' => $payload
                    ]);
                
                case 'invalid':
                    return view('qr.verify',[
                       'status' => 'invalid',
                        'message' => 'Ce QR Code est invalide.',
                        'qrStamp' => $qrStamp,
                        'payload' => $payload
                    ]);
                
                default:
                    return view('qr.verify',[
                         'status' => 'error',
                        'message' => 'Statut du QR Code inconnu.',
                        'qrStamp' => $qrStamp,
                        'payload' => $payload
                    ]);
            }


        } catch (\Exception $e) {
             Log::error('Erreur lors de la vérification du QR Code', [
                'error' => $e->getMessage(),
                'token' => substr($token ?? '', 0, 10) . '...',
                'trace' => $e->getTraceAsString()
            ]);

            return view('qr.verify', [
                'status' => 'error',
                'message' => 'Une erreur est survenue lors de la vérification : ' . $e->getMessage(),
                'qrStamp' => null
            ]);
        }
    }
}

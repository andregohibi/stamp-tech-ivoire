<?php

namespace App\Services;

use App\Models\QrStamp;
use App\Models\Signatory;
use App\Models\Company;
use App\Models\User;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QrStampService
{
    private function preparePayload(Signatory $signatory): array
    {
        if (!$signatory->company) {
            throw new \Exception("Signataire non rattaché à une entreprise.");
        }

        return [
            'signatory_id' => $signatory->id,
            'full_name' => $signatory->full_name,
            'position' => $signatory->position,
            'department' => $signatory->department,
            'email' => $signatory->email,
            'phone' => $signatory->phone,
            'signature_image' => $signatory->signature_image,
            'company' => [
                'id' => $signatory->company->id,
                'name' => $signatory->company->name,
                'registration_number' => $signatory->company->registration_number,
            ],
            'issued_at' => now()->toIso8601String(),
        ];
    }

    private function encryptPayload(array $payload): string
    {
        try {
            return Crypt::encryptString(json_encode($payload));
        } catch (\Exception $e) {
            throw new \Exception("Une erreur est survenue lors de l'encryption du payload.");
        }
    }

    private function generateSignatureHash(string $uniqueCode, string $encryptedPayload): string
    {
        $key = env('QR_ENCRYPTION_KEY');
        $data = $uniqueCode . '|' . $encryptedPayload;

        if (empty($key)) {
            throw new \Exception("La clé de chiffrement est manquante.");
        }

        return hash_hmac('sha256', $data, $key);
    }

    private function generateCodeUnique(): string
    {
        // Génère un token sécurisé (32+ caractères)
        // Utilise random_bytes pour une entropie maximale + UUID pour redundance
        
        $maxAttempts = 10;
        $attempts = 0;

        do {
            // random_bytes(24) = 192 bits, base64url = 32 caractères
            $randomBytes = bin2hex(random_bytes(24)); // 48 caractères en hex
            $timestamp = dechex(time() * 1000); // timestamp en hex
            
            // Combiner et tronquer à 40 caractères pour plus d'entropie
            $code = str_shuffle(substr($randomBytes . $timestamp, 0, 40));

            $attempts++;
            if ($attempts >= $maxAttempts) {
                throw new \Exception("Impossible de générer un code unique après {$maxAttempts} tentatives.");
            }
        } while (QrStamp::where('unique_code', $code)->exists());

        return $code;
    }

    private function generateTokenHash(string $token): string
    {
        // Stocker le SHA256 du token (one-way hash)
        return hash('sha256', $token, false);
    }

    private function getCompanyFolder(Company $company): string
    {
        $companySlug = Str::slug($company->name, '-');
        return "qr-stamps/{$companySlug}";
    }

    private function generateFileName(Signatory $signatory, string $uniqueCode): string
    {
        $fullNameSlug = Str::slug($signatory->full_name, '-');
        return "{$fullNameSlug}_{$uniqueCode}.svg";
    }

    private function generateQrImage(QrStamp $qrStamp): string
    {
        try {
            // URL de vérification sécurisée: /api/qr/verify/{token}
            // Le token brut est dans l'URL, le hash est stocké en DB
            $verificationUrl = route('qr.verify', ['token' => $qrStamp->unique_code]);

            $qrCodeImage = QrCode::format('svg')
                ->size(300)
                ->backgroundColor(255, 255, 255)
                ->color(0, 0, 0)
                ->generate($verificationUrl);

            $companyFolder = $this->getCompanyFolder($qrStamp->company);
            $fileName = $this->generateFileName($qrStamp->signatory, $qrStamp->unique_code);
            $fullPath = "{$companyFolder}/{$fileName}";

            if (! Storage::disk('public')->exists($companyFolder)) {
                Storage::disk('public')->makeDirectory($companyFolder);
            }

            $saved = Storage::disk('public')->put($fullPath, $qrCodeImage);

            if (! $saved) {
                throw new \Exception("Impossible de sauvegarder l'image du QR code.");
            }

            return $fullPath;
        } catch (\Throwable $e) {
            Log::error('Erreur generateQrImage: '.$e->getMessage(), [
                'qr_stamp_id' => $qrStamp->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function createQrStamp(Signatory $signatory, array $data = []): QrStamp
    {
        return DB::transaction(function () use ($signatory, $data) {
            if (! method_exists($signatory->company, 'canGenerateQr') || ! $signatory->company->canGenerateQr()) {
                // If method doesn't exist, try using qr_quota/qr_used fields
                if (isset($signatory->company->qr_quota) && isset($signatory->company->qr_used)) {
                    if ($signatory->company->qr_used >= $signatory->company->qr_quota) {
                        throw new \Exception("L'entreprise a atteint son quota de QR codes.");
                    }
                } else {
                    throw new \Exception("Impossible de vérifier le quota de l'entreprise.");
                }
            }

            $uniqueCode = $this->generateCodeUnique();
            $tokenHash = $this->generateTokenHash($uniqueCode);

            $payload = $this->preparePayload($signatory);
            $encryptedPayload = $this->encryptPayload($payload);
            $signatureHash = $this->generateSignatureHash($uniqueCode, $encryptedPayload);

            $qrStamp = QrStamp::create([
                'unique_code' => $uniqueCode,
                'token_hash' => $tokenHash, // Stocker le hash du token (sécurisé)
                'company_id' => $signatory->company_id,
                'signatory_id' => $signatory->id,
                'payload_encrypted' => $encryptedPayload,
                'signature_hash' => $signatureHash,
                'encryption_key_version' => 'v1.0',
                'status' => 'active',
                'issued_at' => now(),
                'expires_at' => $data['expires_at'] ?? now()->addYear(),
                'created_by' => Auth::check() ? Auth::user()->name : null,
                'metadata' => [
                    'notes' => $signatory->notes,
                    'generated_by_user' => Auth::check() ? Auth::user()->name : null,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'signature_image' => $signatory->signature_image,
                ],
            ]);

            $qrImagePath = $this->generateQrImage($qrStamp);
            $qrStamp->update(['qr_image_path' => $qrImagePath]);

            if (method_exists($signatory->company, 'incrementQrUsed')) {
                $signatory->company->incrementQrUsed();
            } else {
                if (isset($signatory->company->qr_used)) {
                    $signatory->company->increment('qr_used');
                }
            }

            return $qrStamp->refresh();
        });
    }

    private function applySignatoryUpdates(Signatory $signatory, array $data): void
    {
        $updatable = array_filter([
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'email' => $data['email'] ?? null,
            'position' => $data['position'] ?? null,
            'department' => $data['department'] ?? null,
            'phone' => $data['phone'] ?? null,
            'signature_image' => $data['signature_image'] ?? null,
            'notes' => $data['notes'] ?? null,
        ], fn ($v) => $v !== null);

        if (! empty($updatable)) {
            $signatory->update($updatable);
        }
    }

     public function updateQrStamp(Signatory $signatory, array $data = []): QrStamp
    {
        return DB::transaction(function () use ($signatory, $data) {
            $qrStamp = $signatory->qrStamp;

            if (! $qrStamp) {
                throw new \Exception('Aucun QrStamp trouvé pour mise à jour.');
            }

            $codeUnique = $qrStamp->unique_code;

            $this->applySignatoryUpdates($signatory, $data);

            $payload = $this->preparePayload($signatory);
            $encryptedPayload = $this->encryptPayload($payload);
            $signatureHash = $this->generateSignatureHash($codeUnique, $encryptedPayload);

            $qrStamp->update([
                'payload_encrypted' => $encryptedPayload,
                'signature_hash' => $signatureHash,
                'issued_at' => now(),
            ]);

            if (isset($data['expires_at'])) {
                $qrStamp->update(['expires_at' => $data['expires_at']]);
            }

            return $qrStamp->fresh();
        });
    } 

    /* public function updateQrStamp(Signatory $signatory, array $data = []): QrStamp
    {
        return DB::transaction(function () use ($signatory, $data) {
            $qrStamp = $signatory->qrStamp;

            if (! $qrStamp) {
                throw new \Exception('Aucun QrStamp trouvé pour mise à jour.');
            }

            // Supprimer l'ancienne image QR
            if ($qrStamp->qr_image_path && Storage::disk('public')->exists($qrStamp->qr_image_path)) {
                Storage::disk('public')->delete($qrStamp->qr_image_path);
            }

            // Générer un nouveau code unique et son hash
            $newUniqueCode = $this->generateCodeUnique();
            $newTokenHash = $this->generateTokenHash($newUniqueCode);

            $this->applySignatoryUpdates($signatory, $data);

            $payload = $this->preparePayload($signatory);
            $encryptedPayload = $this->encryptPayload($payload);
            $signatureHash = $this->generateSignatureHash($newUniqueCode, $encryptedPayload);

            $qrStamp->update([
                'unique_code' => $newUniqueCode,
                'token_hash' => $newTokenHash,
                'payload_encrypted' => $encryptedPayload,
                'signature_hash' => $signatureHash,
                'issued_at' => now(),
            ]);

            if (isset($data['expires_at'])) {
                $qrStamp->update(['expires_at' => $data['expires_at']]);
            }

            // Régénérer l'image QR avec le nouveau code
            $newQrImagePath = $this->generateQrImage($qrStamp);
            $qrStamp->update(['qr_image_path' => $newQrImagePath]);

            return $qrStamp->fresh();
        });
    } */



    


}

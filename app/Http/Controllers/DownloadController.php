<?php

namespace App\Http\Controllers;

use App\Models\QrStamp;
use App\Models\Signatory;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class DownloadController extends Controller
{
/**
 * Télécharger la signature d'un signataire
 *
 * @param Signatory $signatory
 * @return \Illuminate\Http\Response
 */
    public function download(Signatory $signatory): BinaryFileResponse|Response
    
    {
       // Vérifier que le signataire a un QR Stamp
        if (!$signatory->hasQrStamp()) {
            abort(404, 'Aucun QR Code trouvé pour ce signataire.');
        }

        $qrStamp = $signatory->qrStamp;

        // Vérifier que le chemin existe
        if (empty($qrStamp->qr_image_path)) {
            abort(404, 'Le chemin du QR Code est invalide.');
        }

        $fullPath = Storage::disk('public')->path($qrStamp->qr_image_path);

        // Vérifier que le fichier existe physiquement
        if (!Storage::disk('public')->exists($qrStamp->qr_image_path)) {
            abort(404, 'Le fichier QR Code est introuvable sur le serveur.');
        }

        // Générer un nom de fichier propre pour le téléchargement
        $downloadName = $this->generateDownloadFileName($signatory, $qrStamp);

        // Retourner le fichier en téléchargement
        return response()->download(
            $fullPath,
            $downloadName,
            [
                'Content-Type' => 'image/svg+xml',
                'Content-Disposition' => 'attachment; filename="' . $downloadName . '"',
            ]
        );
    }


     /**
     * Générer un nom de fichier propre pour le téléchargement
     *
     * @param Signatory $signatory
     * @param QrStamp $qrStamp
     * @return string
     */
    private function generateDownloadFileName(Signatory $signatory, QrStamp $qrStamp): string
    {
        $fullName = Str::slug($signatory->full_name, '-');
        $company = Str::slug($signatory->company->name ?? 'company', '-');
        $code = $qrStamp->unique_code;
        $date = now()->format('Y-m-d');

        return "qr-code_{$fullName}_{$company}_{$code}_{$date}.svg";
    }

    /**
     * Prévisualiser le QR Code dans le navigateur
     *
     * @param Signatory $signatory
     * @return Response
     */
    public function preview(Signatory $signatory): Response
    {
        if (!$signatory->hasQrStamp()) {
            abort(404, 'Aucun QR Code trouvé pour ce signataire.');
        }

        $qrStamp = $signatory->qrStamp;

        if (!Storage::disk('public')->exists($qrStamp->qr_image_path)) {
            abort(404, 'Le fichier QR Code est introuvable.');
        }

        $content = Storage::disk('public')->get($qrStamp->qr_image_path);

        return response($content, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}

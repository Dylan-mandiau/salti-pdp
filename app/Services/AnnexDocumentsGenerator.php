<?php

namespace App\Services;

use App\Models\Pdp;
use Illuminate\Support\Facades\View;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

/**
 * Génère les documents annexes pré-remplis :
 *  - Permis feu (Blade : pdf.permis-feu)
 *  - Convention de prêt (Blade : pdf.convention-pret)
 *
 * Renvoie les chemins relatifs des fichiers générés (storage/app/...).
 */
class AnnexDocumentsGenerator
{
    /**
     * Génère le PDF Permis feu pré-rempli pour ce PDP.
     * Retourne le chemin relatif (storage/app/pdp/{uuid}/permis-feu.pdf).
     */
    public function generatePermisFeu(Pdp $pdp): string
    {
        $mpdf = $this->createMpdf();
        $mpdf->SetTitle('Permis feu - '.$pdp->uuid);

        $html = View::make('pdf.permis-feu', [
            'pdp' => $pdp,
            'data' => $pdp->data,
            'pf' => $pdp->data['permis_feu'] ?? [],
            'assets' => public_path('pdf-assets').'/',
        ])->render();

        $mpdf->WriteHTML($html);

        $relativePath = "pdp/{$pdp->uuid}/permis-feu.pdf";
        $absolutePath = storage_path('app/'.$relativePath);
        if (! is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0775, true);
        }
        $mpdf->Output($absolutePath, \Mpdf\Output\Destination::FILE);
        return $relativePath;
    }

    /**
     * Génère le PDF Convention de prêt pré-rempli pour ce PDP.
     */
    public function generateConventionPret(Pdp $pdp): string
    {
        $mpdf = $this->createMpdf();
        $mpdf->SetTitle('Convention de pret - '.$pdp->uuid);

        $html = View::make('pdf.convention-pret', [
            'pdp' => $pdp,
            'data' => $pdp->data,
            'agency' => $pdp->agency,
            'assets' => public_path('pdf-assets').'/',
        ])->render();

        $mpdf->WriteHTML($html);

        $relativePath = "pdp/{$pdp->uuid}/convention-pret.pdf";
        $absolutePath = storage_path('app/'.$relativePath);
        if (! is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0775, true);
        }
        $mpdf->Output($absolutePath, \Mpdf\Output\Destination::FILE);
        return $relativePath;
    }

    private function createMpdf(): Mpdf
    {
        return new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 10,
            'margin_bottom' => 14,
            'default_font' => 'arial',
            'tempDir' => storage_path('app/mpdf-tmp'),
        ]);
    }
}

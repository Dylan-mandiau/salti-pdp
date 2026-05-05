<?php

namespace App\Services;

use App\Models\Pdp;
use Illuminate\Support\Facades\View;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

/**
 * Génère le PDF SALTI **reconstruit en HTML/CSS** (pas d'overlay sur l'original).
 *
 * Avantages vs overlay :
 *  - Aucune calibration de coordonnées
 *  - Mise en page parfaite, exécutée par le moteur HTML de mPDF
 *  - Lignes adaptables (signataires, autres risques, habilitations)
 *  - Texte UTF-8 natif (accents OK)
 *
 * Les assets visuels (logo, bandeau jaune, image consigne accident, pictogrammes EPI)
 * sont les MÊMES que ceux du PDF officiel, extraits avec pdfimages — donc le rendu
 * visuel est strictement identique.
 */
class PdpHtmlPdfGenerator
{
    /**
     * Génère le PDF complet et retourne le chemin relatif (storage/app/...) du fichier.
     */
    public function generate(Pdp $pdp): string
    {
        $mpdf = $this->createMpdf();
        $mpdf->SetTitle('Plan de Prevention - '.$pdp->uuid);
        $mpdf->SetAuthor('SALTI');
        $mpdf->SetCreator('PDP SALTI');

        // Pieds de page communs (numérotation X sur 6)
        $mpdf->SetHTMLFooter(
            '<div style="text-align:center;font-size:8pt;color:#000;">
                SALTI - Sécurité – Plan de prévention 2026 &nbsp;&nbsp;&nbsp;
                Version du 02/12/2025 &nbsp;&nbsp;&nbsp;
                {PAGENO} sur {nbpg}
            </div>'
        );

        // Construit le HTML complet (1 string, on @page-break entre les pages)
        $html = View::make('pdf.pdp', [
            'pdp' => $pdp,
            'data' => $pdp->data,
            'intervenants' => $pdp->intervenants,
            'agency' => $pdp->agency,
            'assets' => public_path('pdf-assets').'/',
        ])->render();

        $mpdf->WriteHTML($html);

        $relativePath = "pdp/{$pdp->uuid}/plan-de-prevention.pdf";
        $absolutePath = storage_path('app/'.$relativePath);
        if (! is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0775, true);
        }
        $mpdf->Output($absolutePath, \Mpdf\Output\Destination::FILE);

        return $relativePath;
    }

    private function createMpdf(): Mpdf
    {
        $defaultConfig = (new ConfigVariables())->getDefaults();
        $defaultFontConfig = (new FontVariables())->getDefaults();

        return new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 8,
            'margin_bottom' => 18,
            'margin_header' => 4,
            'margin_footer' => 6,
            'default_font' => 'arial',
            'tempDir' => storage_path('app/mpdf-tmp'),
            'fontDir' => array_merge($defaultConfig['fontDir'] ?? [], []),
            'fontdata' => $defaultFontConfig['fontdata'] ?? [],
        ]);
    }
}

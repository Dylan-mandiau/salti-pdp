<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Pdp;

// TCPDF requis pour FPDI-TCPDF
require_once base_path('vendor/tecnickcom/tcpdf/tcpdf.php');

use setasign\Fpdi\Tcpdf\Fpdi;

/**
 * Service qui attache des annexes au PDF principal d'un PDP.
 *
 * Annexes possibles selon ce qui est coché et présent :
 *  - Plan d'accès / circulation (spécifique à l'agence)
 *  - Permis feu (global QSE)
 *  - Convention de prêt de matériel (global QSE)
 *  - Tous les fichiers uploadés par le prestataire
 *
 * Les PDF sont importés page par page. Les images (JPG/PNG) sont
 * placées sur une nouvelle page A4 avec un titre.
 */
class PdfAnnexService
{
    public function __construct(private AnnexDocumentsGenerator $docsGenerator) {}

    /**
     * Liste les annexes à attacher en fonction de l'état du PDP.
     *
     * Note : le Plan d'accès agence n'est PAS attaché au PDF final pour 2 raisons :
     *  - SALTI a déjà ces plans en local
     *  - Beaucoup de plans d'accès sont des PDF générés par des outils modernes
     *    qui utilisent une compression que la version libre de FPDI ne sait pas
     *    importer (erreur "PDF document probably uses a compression technique...")
     * Le presta y accède via le bouton dédié dans son récap "Vos documents".
     *
     * @return array<int, array{path: string, label: string, type: string}>
     */
    public function listAnnexes(Pdp $pdp): array
    {
        $annexes = [];
        $data = $pdp->data ?? [];

        // 1. Permis feu pré-rempli (généré dynamiquement) si case cochée
        if (! empty($data['documents_remis_ee']['permis_feu'])) {
            $path = $this->docsGenerator->generatePermisFeu($pdp);
            $annexes[] = [
                'path' => storage_path('app/'.$path),
                'label' => 'Permis feu — pré-rempli',
                'type' => 'generated',
            ];
        }

        // 2. Convention de prêt pré-remplie (générée) si case cochée + matériels listés
        if (! empty($data['documents_remis_ee']['convention_pret'])) {
            $hasMateriels = collect($data['materiels_pretes'] ?? [])
                ->filter(fn($m) => ! empty($m['designation']))->isNotEmpty();
            if ($hasMateriels) {
                $path = $this->docsGenerator->generateConventionPret($pdp);
                $annexes[] = [
                    'path' => storage_path('app/'.$path),
                    'label' => 'Convention de prêt de matériel — pré-remplie',
                    'type' => 'generated',
                ];
            }
        }

        // 3. Tous les fichiers uploadés par le prestataire (CACES, habilitations, autres)
        foreach ($pdp->documents()->where('uploaded_by', 'prestataire')->get() as $doc) {
            $annexes[] = [
                'path' => storage_path('app/'.$doc->path),
                'label' => $doc->original_filename,
                'type' => 'prestataire',
                'category' => $doc->type,
            ];
        }

        return $annexes;
    }

    /**
     * Attache les annexes au PDF principal et retourne le chemin du fichier final.
     * Si aucune annexe : retourne $mainPdfPath inchangé.
     */
    public function appendAnnexes(string $mainPdfPath, Pdp $pdp): string
    {
        $annexes = $this->listAnnexes($pdp);
        if (empty($annexes)) {
            return $mainPdfPath;
        }

        if (! file_exists($mainPdfPath)) {
            throw new \RuntimeException("PDF principal introuvable : $mainPdfPath");
        }

        $merged = new Fpdi('P', 'mm', 'A4');
        $merged->setPrintHeader(false);
        $merged->setPrintFooter(false);
        $merged->SetCreator('PDP SALTI');

        // 1. Importe toutes les pages du PDF principal
        $pageCount = $merged->setSourceFile($mainPdfPath);
        for ($i = 1; $i <= $pageCount; $i++) {
            $tplIdx = $merged->importPage($i);
            $size = $merged->getTemplateSize($tplIdx);
            $merged->AddPage('P', [$size['width'], $size['height']]);
            $merged->useTemplate($tplIdx);
        }

        // 2. Ajoute une page "Sommaire des annexes"
        $this->addAnnexCover($merged, $annexes);

        // 3. Pour chaque annexe : import si PDF, image-page si JPG/PNG
        foreach ($annexes as $annex) {
            if (! file_exists($annex['path'])) {
                continue;
            }
            $ext = strtolower(pathinfo($annex['path'], PATHINFO_EXTENSION));
            if ($ext === 'pdf') {
                $this->appendPdfFile($merged, $annex['path'], $annex['label']);
            } elseif (in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
                $this->appendImageFile($merged, $annex['path'], $annex['label']);
            }
            // .docx etc. ignorés (TCPDF/FPDI ne sait pas les lire) — on les laisse en pièce jointe via UI
        }

        $merged->Output($mainPdfPath, 'F');
        return $mainPdfPath;
    }

    /**
     * Page de garde listant les annexes.
     * Pas d'emojis : la police helvetica de TCPDF ne les supporte pas
     * et les afficherait sous forme de '?'.
     */
    private function addAnnexCover(Fpdi $pdf, array $annexes): void
    {
        $pdf->AddPage('P', 'A4');
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->SetFillColor(255, 192, 0); // jaune SALTI
        $pdf->Cell(0, 12, 'ANNEXES AU PLAN DE PRÉVENTION', 0, 1, 'C', true);
        $pdf->Ln(8);

        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(0, 6, 'Les documents suivants sont annexés au présent PDP :', 0, 1);
        $pdf->Ln(4);

        $pdf->SetFont('helvetica', '', 10);
        foreach ($annexes as $i => $annex) {
            $num = $i + 1;
            $typeLabel = match ($annex['type']) {
                'generated' => 'SALTI',
                'prestataire' => 'Prestataire',
                default => '',
            };
            $pdf->Cell(15, 6, $num.'.', 0, 0);
            $prefix = $typeLabel ? '['.$typeLabel.']  '.$annex['label'] : $annex['label'];
            $pdf->Cell(0, 6, $prefix, 0, 1);
        }
    }

    /**
     * Importe les pages d'un PDF en annexe.
     */
    private function appendPdfFile(Fpdi $pdf, string $pdfPath, string $label): void
    {
        try {
            $count = $pdf->setSourceFile($pdfPath);
            for ($i = 1; $i <= $count; $i++) {
                $tplIdx = $pdf->importPage($i);
                $size = $pdf->getTemplateSize($tplIdx);
                $pdf->AddPage($size['width'] > $size['height'] ? 'L' : 'P', [$size['width'], $size['height']]);
                $pdf->useTemplate($tplIdx);

                // Petit titre en haut, en italique discret (sans emoji)
                if ($i === 1) {
                    $pdf->SetFont('helvetica', 'I', 8);
                    $pdf->SetTextColor(120);
                    $pdf->SetXY(5, 5);
                    $pdf->Cell(0, 5, 'Annexe : '.$label, 0, 0);
                    $pdf->SetTextColor(0);
                }
            }
        } catch (\Throwable $e) {
            // PDF corrompu, protégé ou utilisant une compression non supportée :
            // page d'erreur lisible, sans message technique en anglais.
            $pdf->AddPage('P', 'A4');
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, 'Annexe : '.$label, 0, 1);
            $pdf->Ln(4);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->MultiCell(0, 6,
                "Ce document n'a pas pu être intégré au PDP final.\n".
                "Il reste disponible séparément dans la liste des documents du PDP.",
                0, 'L'
            );
        }
    }

    /**
     * Insère une image en annexe (taille auto-ajustée à A4).
     */
    private function appendImageFile(Fpdi $pdf, string $imgPath, string $label): void
    {
        $pdf->AddPage('P', 'A4');
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Annexe : '.$label, 0, 1);

        // Calcule les dimensions max (210x297 mm A4 - marges)
        $maxW = 190;
        $maxH = 250;
        try {
            [$w, $h] = getimagesize($imgPath);
            $ratio = min($maxW / ($w * 0.264583), $maxH / ($h * 0.264583)); // px → mm
            $finalW = $w * 0.264583 * $ratio;
            $finalH = $h * 0.264583 * $ratio;
            $pdf->Image($imgPath, 10, 25, $finalW, $finalH);
        } catch (\Throwable $e) {
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 8, "Image non insérable.", 0, 1);
        }
    }
}

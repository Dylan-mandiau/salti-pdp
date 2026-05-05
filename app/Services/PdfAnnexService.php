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
    /**
     * Liste les annexes à attacher en fonction de l'état du PDP.
     * @return array<int, array{path: string, label: string, type: string}>
     */
    public function listAnnexes(Pdp $pdp): array
    {
        $annexes = [];
        $data = $pdp->data ?? [];

        // 1. Plan d'accès — spécifique à l'agence, si la case est cochée
        if (! empty($data['documents_remis_ee']['plan_acces']) && $pdp->agency?->access_plan_path) {
            $annexes[] = [
                'path' => storage_path('app/'.$pdp->agency->access_plan_path),
                'label' => 'Plan d\'accès / circulation — '.($pdp->agency->city ?? $pdp->agency->name),
                'type' => 'agency',
            ];
        }

        // 2. Permis feu — global, si la case est cochée
        $permisFeuPath = AppSetting::get('permis_feu_path');
        if (! empty($data['documents_remis_ee']['permis_feu']) && $permisFeuPath) {
            $annexes[] = [
                'path' => storage_path('app/'.$permisFeuPath),
                'label' => 'Permis feu',
                'type' => 'global',
            ];
        }

        // 3. Convention de prêt — global, si la case est cochée
        $conventionPath = AppSetting::get('convention_pret_path');
        if (! empty($data['documents_remis_ee']['convention_pret']) && $conventionPath) {
            $annexes[] = [
                'path' => storage_path('app/'.$conventionPath),
                'label' => 'Convention de prêt de matériel',
                'type' => 'global',
            ];
        }

        // 4. Tous les fichiers uploadés par le prestataire (CACES, habilitations, autres)
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
                'agency' => '🏢 Agence',
                'global' => '🏛 SALTI (global)',
                'prestataire' => '👷 Prestataire',
                default => '📄',
            };
            $pdf->Cell(15, 6, $num.'.', 0, 0);
            $pdf->Cell(0, 6, $typeLabel.'  —  '.$annex['label'], 0, 1);
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

                // Petit titre en bas (sauf si page paysage où on met en haut)
                if ($i === 1) {
                    $pdf->SetFont('helvetica', 'I', 8);
                    $pdf->SetTextColor(120);
                    $pdf->SetXY(5, 5);
                    $pdf->Cell(0, 5, '📎 Annexe : '.$label, 0, 0);
                    $pdf->SetTextColor(0);
                }
            }
        } catch (\Throwable $e) {
            // PDF corrompu ou protégé : ajout d'une page d'erreur plutôt que de tout planter
            $pdf->AddPage('P', 'A4');
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, 'Annexe : '.$label, 0, 1);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 8, '⚠ PDF non importable : '.$e->getMessage(), 0, 1);
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
            $pdf->Cell(0, 8, '⚠ Image non insérable : '.$e->getMessage(), 0, 1);
        }
    }
}

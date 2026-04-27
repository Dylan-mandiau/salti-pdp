<?php

namespace App\Services;

// TCPDF n'est pas chargé via PSR-4 — le require_once garantit qu'il est dispo
// avant que setasign\Fpdi\Tcpdf\Fpdi (qui extends \TCPDF) soit autoload.
require_once base_path('vendor/tecnickcom/tcpdf/tcpdf.php');

use App\Models\Pdp;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Tcpdf\Fpdi;

/**
 * Génère le PDF final d'un PDP en superposant les données saisies
 * sur le template officiel SALTI 2026 (storage/app/templates/pdp-salti-2026.pdf).
 *
 * Aucune redessination du document : on charge le PDF officiel comme fond
 * et on écrit par-dessus aux coordonnées définies dans config/pdp_pdf_mapping.php.
 */
class PdpPdfGenerator
{
    private Fpdi $pdf;
    private array $mapping;

    public function __construct()
    {
        $this->mapping = config('pdp_pdf_mapping');
    }

    /**
     * Génère le PDF complet et retourne le chemin du fichier généré sur le disque storage.
     */
    public function generate(Pdp $pdp): string
    {
        $this->pdf = new Fpdi('P', 'mm', 'A4');
        $this->pdf->SetMargins(0, 0, 0);
        $this->pdf->SetAutoPageBreak(false);
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);
        $this->pdf->SetCreator('PDP SALTI');
        $this->pdf->SetTitle('Plan de Prevention - '.$pdp->uuid);

        $templatePath = $this->mapping['template_path'];
        if (! file_exists($templatePath)) {
            throw new \RuntimeException("Template PDF introuvable: $templatePath");
        }

        $pageCount = $this->pdf->setSourceFile($templatePath);
        $data = $pdp->data;

        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
            $tplIdx = $this->pdf->importPage($pageNumber);
            $size = $this->pdf->getTemplateSize($tplIdx);
            $this->pdf->AddPage('P', [$size['width'], $size['height']]);
            $this->pdf->useTemplate($tplIdx);

            $methodName = "fillPage$pageNumber";
            if (method_exists($this, $methodName)) {
                $this->$methodName($data, $pdp);
            }
        }

        $relativePath = "pdp/{$pdp->uuid}/plan-de-prevention.pdf";
        $absolutePath = storage_path('app/'.$relativePath);

        if (! is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0775, true);
        }

        $this->pdf->Output($absolutePath, 'F');

        return $relativePath;
    }

    // ===========================================================================
    // PAGE 1 — Informations générales
    // ===========================================================================
    private function fillPage1(array $data, Pdp $pdp): void
    {
        $m = $this->mapping['page_1'];

        $this->writeText($m['eu_agence'], $data['eu']['agence'] ?? $pdp->agency->city);
        $this->writeText($m['eu_donneur_ordre'], $data['eu']['donneur_ordre'] ?? $pdp->donneur_ordre_nom);
        $this->writeText($m['eu_address'], $data['eu']['address'] ?? $pdp->agency->address);
        $this->writeText($m['eu_phone'], $data['eu']['phone'] ?? $pdp->agency->phone);

        $this->writeText($m['ee_raison_sociale'], $data['ee']['raison_sociale'] ?? null);
        $this->writeText($m['ee_responsable'], $data['ee']['responsable_prestations'] ?? null);
        $this->writeText($m['ee_address'], $data['ee']['address'] ?? null);
        $this->writeText($m['ee_phone'], $data['ee']['phone'] ?? null);

        $sousTraitance = $data['ee']['sous_traitance'] ?? null;
        $this->writeCheckbox($m['ee_sous_traitance_oui'], $sousTraitance === 'oui');
        $this->writeCheckbox($m['ee_sous_traitance_non'], $sousTraitance === 'non');

        $this->writeCheckbox($m['op_ponctuelle'], ($data['operation']['type'] ?? null) === 'ponctuelle');
        $this->writeCheckbox($m['op_annuelle'], ($data['operation']['type'] ?? null) === 'annuelle');
        $this->writeCheckbox($m['op_moins_400h'], ($data['operation']['volume'] ?? null) === 'moins_400h');
        $this->writeCheckbox($m['op_plus_400h'], ($data['operation']['volume'] ?? null) === 'plus_400h');
        $this->writeCheckbox($m['op_travaux_dangereux'], $data['operation']['travaux_dangereux'] ?? false);
        $this->writeText($m['op_designation'], $data['operation']['designation'] ?? null);
        $this->writeText($m['op_lieu'], $data['operation']['lieu'] ?? null);
        $this->writeText($m['op_date_debut'], $data['operation']['date_debut'] ?? null);
        $this->writeText($m['op_duree'], $data['operation']['duree'] ?? null);
        $this->writeText($m['op_plages_horaires'], $data['operation']['plages_horaires'] ?? null);
        $this->writeText($m['op_nb_salaries'], $data['operation']['nb_salaries'] ?? null);

        $this->writeText($m['insp_date'], $data['inspection']['date'] ?? null);
        $this->writeText($m['insp_participants'], $data['inspection']['participants'] ?? null);
        $this->writeText($m['insp_informations'], $data['inspection']['informations_echangees'] ?? null);
        $this->writeText($m['insp_zones'], $data['inspection']['zones_visitees'] ?? null);
        $this->writeText($m['insp_observations'], $data['inspection']['observations_cssct'] ?? null);
        $this->writeCheckbox($m['insp_vestiaires'], $data['inspection']['locaux']['vestiaires'] ?? false);
        $this->writeCheckbox($m['insp_sanitaires'], $data['inspection']['locaux']['sanitaires'] ?? false);
        $this->writeCheckbox($m['insp_refectoire'], $data['inspection']['locaux']['refectoire'] ?? false);
    }

    // ===========================================================================
    // PAGE 2 — Documents et secours
    // ===========================================================================
    private function fillPage2(array $data, Pdp $pdp): void
    {
        $m = $this->mapping['page_2'];

        $this->writeCheckbox($m['doc_plan_acces'], $data['documents_remis_ee']['plan_acces'] ?? false);
        $this->writeCheckbox($m['doc_permis_feu'], $data['documents_remis_ee']['permis_feu'] ?? false);
        $this->writeCheckbox($m['doc_convention_pret'], $data['documents_remis_ee']['convention_pret'] ?? false);

        $this->writeCheckbox($m['doc_autorisation_conduite'], $data['documents_remis_salti']['autorisation_conduite'] ?? false);
        $this->writeCheckbox($m['doc_caces'], $data['documents_remis_salti']['caces'] ?? false);
        $this->writeCheckbox($m['doc_habilitations'], $data['documents_remis_salti']['habilitations'] ?? false);

        $this->writeText($m['sst_nom'], $data['secours']['sst_nom'] ?? null);
        $this->writeText($m['sst_fonction'], $data['secours']['sst_fonction'] ?? null);
        $this->writeText($m['resp_ee_nom'], $data['secours']['resp_ee_nom'] ?? null);
        $this->writeText($m['resp_ee_fonction'], $data['secours']['resp_ee_fonction'] ?? null);
    }

    // ===========================================================================
    // PAGE 3 — EPI + premières lignes du tableau des risques
    // ===========================================================================
    private function fillPage3(array $data, Pdp $pdp): void
    {
        $m = $this->mapping['page_3'];

        $epi = $data['epi'] ?? [];
        $this->writeCheckbox($m['epi_chaussures'], $epi['chaussures'] ?? false);
        $this->writeCheckbox($m['epi_gants'], $epi['gants'] ?? false);
        $this->writeCheckbox($m['epi_casque'], $epi['casque'] ?? false);
        $this->writeCheckbox($m['epi_lunettes'], $epi['lunettes'] ?? false);
        $this->writeCheckbox($m['epi_masque'], $epi['masque'] ?? false);
        $this->writeCheckbox($m['epi_auditives'], $epi['auditives'] ?? false);
        $this->writeCheckbox($m['epi_gilet_hv'], $epi['gilet_hv'] ?? false);
        $this->writeCheckbox($m['epi_harnais'], $epi['harnais'] ?? false);
        $this->writeText($m['epi_autres'], $epi['autres'] ?? null);

        $risques = $data['risques'] ?? [];
        foreach (['arrivee_site', 'circulation_interne', 'stationnement', 'sols_souilles'] as $key) {
            $coordKey = "risk_{$key}_applicable";
            if (isset($m[$coordKey])) {
                $this->writeCheckbox($m[$coordKey], $risques[$key]['applicable'] ?? false);
            }
        }
    }

    // ===========================================================================
    // PAGE 4 — Suite des risques
    // ===========================================================================
    private function fillPage4(array $data, Pdp $pdp): void
    {
        $m = $this->mapping['page_4'];
        $risques = $data['risques'] ?? [];

        foreach (['travail_hauteur', 'levage_manutention', 'soudure_decoupe', 'dechets', 'electrique', 'produits_chimiques', 'flexibles_engins'] as $key) {
            $coordKey = "risk_{$key}_applicable";
            if (isset($m[$coordKey])) {
                $this->writeCheckbox($m[$coordKey], $risques[$key]['applicable'] ?? false);
            }
        }
    }

    // ===========================================================================
    // PAGE 5 — Fin des risques + autres + habilitations
    // ===========================================================================
    private function fillPage5(array $data, Pdp $pdp): void
    {
        $m = $this->mapping['page_5'];
        $risques = $data['risques'] ?? [];

        foreach (['multi_interventions', 'contamination'] as $key) {
            $coordKey = "risk_{$key}_applicable";
            if (isset($m[$coordKey])) {
                $this->writeCheckbox($m[$coordKey], $risques[$key]['applicable'] ?? false);
            }
        }

        // Autres risques (libres)
        $autres = $data['autres_risques'] ?? [];
        foreach ($autres as $i => $autre) {
            $idx = $i + 1;
            if ($idx > 5) break; // 5 lignes max sur la page

            $this->writeText($m["autre_risque_{$idx}_situation"] ?? null, $autre['situation'] ?? null);
            $this->writeText($m["autre_risque_{$idx}_risque"] ?? null, $autre['risque'] ?? null);
            $this->writeText($m["autre_risque_{$idx}_mesure"] ?? null, $autre['mesure'] ?? null);
            $this->writeCheckbox($m["autre_risque_{$idx}_eu"] ?? null, $autre['eu'] ?? false);
            $this->writeCheckbox($m["autre_risque_{$idx}_ee"] ?? null, $autre['ee'] ?? false);
        }

        // Habilitations / CACES (3 premières)
        $intervenants = $pdp->intervenants()->whereNotNull('habilitation')->take(3)->get();
        foreach ($intervenants as $i => $iv) {
            $idx = $i + 1;
            $this->writeText($m["hab_{$idx}_salarie"] ?? null, $iv->nom_prenom);
            $this->writeText($m["hab_{$idx}_habilitation"] ?? null, $iv->habilitation);
            $this->writeText($m["hab_{$idx}_validity"] ?? null, $iv->habilitation_validity?->format('d/m/Y'));
        }
    }

    // ===========================================================================
    // PAGE 6 — Attestation et signatures
    // ===========================================================================
    private function fillPage6(array $data, Pdp $pdp): void
    {
        $m = $this->mapping['page_6'];

        $intervenants = $pdp->intervenants()->take(4)->get();
        foreach ($intervenants as $i => $iv) {
            $idx = $i + 1;
            $this->writeText($m["intervenant_{$idx}_nom"] ?? null, $iv->nom_prenom);
            $this->writeText($m["intervenant_{$idx}_date"] ?? null, $iv->date_signature?->format('d/m/Y'));
            $this->writeImageFromDataUrl($m["intervenant_{$idx}_signature"] ?? null, $iv->signature_data);
        }

        // Signatures finales des représentants
        $this->writeText($m['sign_salti_nom'], $pdp->donneur_ordre_nom);
        $this->writeText($m['sign_salti_fonction'], $data['signature_salti_fonction'] ?? null);
        $this->writeText($m['sign_salti_date'], $pdp->signed_by_salti_at?->format('d/m/Y'));
        $this->writeImageFromDataUrl($m['sign_salti_image'], $data['signature_salti'] ?? null);

        $this->writeText($m['sign_ee_nom'], $data['ee']['responsable_prestations'] ?? null);
        $this->writeText($m['sign_ee_fonction'], $data['signature_ee_fonction'] ?? null);
        $this->writeText($m['sign_ee_date'], $pdp->signed_by_prestataire_at?->format('d/m/Y'));
        $this->writeImageFromDataUrl($m['sign_ee_image'], $data['signature_ee'] ?? null);
    }

    // ===========================================================================
    // Helpers
    // ===========================================================================

    private function writeText(?array $coord, ?string $value): void
    {
        if (! $coord || $value === null || $value === '') {
            return;
        }

        $font = $this->mapping['default_font'];
        $this->pdf->SetFont($font['family'], $font['style'], $font['size']);
        $this->pdf->SetTextColor(...$font['color']);
        $this->pdf->SetXY($coord['x'], $coord['y']);

        $width = $coord['w'] ?? 0;
        if ($width > 0) {
            $this->pdf->Cell($width, 4, $value, 0, 0, 'L');
        } else {
            $this->pdf->Write(4, $value);
        }
    }

    private function writeCheckbox(?array $coord, bool $checked): void
    {
        if (! $coord || ! $checked) {
            return;
        }

        $cb = $this->mapping['checkbox'];
        $font = $this->mapping['default_font'];
        $this->pdf->SetFont($font['family'], 'B', $cb['font_size']);
        $this->pdf->SetTextColor(...$font['color']);
        $this->pdf->SetXY($coord['x'], $coord['y']);
        $this->pdf->Write(4, $cb['mark']);
    }

    private function writeImageFromDataUrl(?array $coord, ?string $dataUrl): void
    {
        if (! $coord || ! $dataUrl || ! str_starts_with($dataUrl, 'data:image/')) {
            return;
        }

        // Extract base64 payload
        $parts = explode(',', $dataUrl, 2);
        if (count($parts) !== 2) {
            return;
        }
        $binary = base64_decode($parts[1]);
        if ($binary === false) {
            return;
        }

        // Write to a temporary file (FPDI/TCPDF needs a file path)
        $tmpPath = tempnam(sys_get_temp_dir(), 'sig_').'.png';
        file_put_contents($tmpPath, $binary);

        try {
            $this->pdf->Image($tmpPath, $coord['x'], $coord['y'], $coord['w'] ?? 50, $coord['h'] ?? 15, 'PNG');
        } finally {
            @unlink($tmpPath);
        }
    }
}

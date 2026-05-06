<?php

namespace App\Services;

use App\Models\Pdp;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/**
 * Génère un ZIP d'export à partir d'une sélection de PDPs (QSE only).
 *
 * Structure du ZIP (Option B — par agence puis par client) :
 *   SALTI_PDPs_export_YYYY-MM-DD.zip
 *   ├── Bordeaux/
 *   │   └── DUPONT TP SARL/
 *   │       ├── 2026-05-15 - Remplacement porte sectionnelle - PDP.pdf
 *   │       ├── 2026-05-15 - Remplacement porte sectionnelle - Permis feu.pdf
 *   │       ├── 2026-05-15 - Remplacement porte sectionnelle - Convention.pdf
 *   │       └── pieces-jointes/
 *   │           └── caces-tony.pdf
 *   └── Amiens/
 *       └── IT TECH/
 *           └── …
 */
class PdpZipExporter
{
    public function __construct(
        private PdpHtmlPdfGenerator $pdpGenerator,
        private AnnexDocumentsGenerator $annexGenerator,
    ) {}

    /**
     * Construit un ZIP en streaming et le renvoie comme réponse HTTP.
     *
     * @param Collection<int, Pdp> $pdps  Sélection à exporter
     */
    public function streamZip(Collection $pdps, ?string $zipName = null): StreamedResponse
    {
        $filename = $zipName ?: 'SALTI_PDPs_export_'.now()->format('Y-m-d').'.zip';

        return new StreamedResponse(function () use ($pdps) {
            // ZIP construit dans un fichier temporaire (ZipArchive ne supporte pas
            // bien les flux purs, on passe par un tmpfile et on le stream à la fin)
            $tmpPath = tempnam(sys_get_temp_dir(), 'pdpzip_');
            $zip = new ZipArchive();
            if ($zip->open($tmpPath, ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Impossible de créer l\'archive ZIP.');
            }

            foreach ($pdps as $pdp) {
                $this->addPdpToZip($zip, $pdp);
            }

            $zip->close();

            // Stream le tmpfile dans la réponse, puis le supprime
            $h = fopen($tmpPath, 'rb');
            while (! feof($h)) {
                echo fread($h, 8192);
                @flush();
            }
            fclose($h);
            @unlink($tmpPath);
        }, 200, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Ajoute un PDP (PDP principal + Permis feu + Convention + pièces jointes presta)
     * au ZIP, dans le sous-dossier {Agence}/{Raison sociale presta}/.
     */
    private function addPdpToZip(ZipArchive $zip, Pdp $pdp): void
    {
        $agency = $this->sanitize($pdp->agency?->city ?? $pdp->agency?->name ?? 'Sans-agence');
        $client = $this->sanitize(
            $pdp->prestataire?->raison_sociale
            ?? $pdp->data['ee']['raison_sociale']
            ?? 'Sans-client'
        );
        $datePart = $this->extractDatePrefix($pdp);
        $designation = $this->sanitize(
            \Illuminate\Support\Str::limit($pdp->data['operation']['designation'] ?? 'Operation', 60, '')
        );

        $folder = $agency.'/'.$client;
        $baseName = $datePart.' - '.$designation;

        // 1. PDP principal
        try {
            $pdpPath = $this->pdpGenerator->generate($pdp);
            $absPath = storage_path('app/'.$pdpPath);
            if (file_exists($absPath)) {
                $zip->addFile($absPath, $folder.'/'.$baseName.' - PDP.pdf');
            }
        } catch (\Throwable $e) {
            \Log::warning('Échec génération PDP pour ZIP', ['pdp_id' => $pdp->id, 'err' => $e->getMessage()]);
        }

        // 2. Permis feu si coché
        if (! empty($pdp->data['documents_remis_ee']['permis_feu'])) {
            try {
                $pfPath = $this->annexGenerator->generatePermisFeu($pdp);
                $absPf = storage_path('app/'.$pfPath);
                if (file_exists($absPf)) {
                    $zip->addFile($absPf, $folder.'/'.$baseName.' - Permis feu.pdf');
                }
            } catch (\Throwable $e) {
                \Log::warning('Échec génération Permis feu', ['pdp_id' => $pdp->id, 'err' => $e->getMessage()]);
            }
        }

        // 3. Convention de prêt si cochée + matériels listés
        if (! empty($pdp->data['documents_remis_ee']['convention_pret'])) {
            $hasMateriels = collect($pdp->data['materiels_pretes'] ?? [])
                ->filter(fn($m) => ! empty($m['designation']))->isNotEmpty();
            if ($hasMateriels) {
                try {
                    $cvPath = $this->annexGenerator->generateConventionPret($pdp);
                    $absCv = storage_path('app/'.$cvPath);
                    if (file_exists($absCv)) {
                        $zip->addFile($absCv, $folder.'/'.$baseName.' - Convention.pdf');
                    }
                } catch (\Throwable $e) {
                    \Log::warning('Échec génération Convention', ['pdp_id' => $pdp->id, 'err' => $e->getMessage()]);
                }
            }
        }

        // 4. Pièces jointes du prestataire (CACES, habilitations, etc.)
        $attachments = $pdp->documents()->where('uploaded_by', 'prestataire')->get();
        foreach ($attachments as $doc) {
            $absDoc = storage_path('app/'.$doc->path);
            if (! file_exists($absDoc)) continue;
            $docName = $this->sanitize(pathinfo($doc->original_filename, PATHINFO_FILENAME));
            $ext = pathinfo($doc->original_filename, PATHINFO_EXTENSION) ?: 'bin';
            $zip->addFile($absDoc, $folder.'/pieces-jointes/'.$docName.'.'.$ext);
        }
    }

    /**
     * Extrait la date la plus pertinente pour le nommage : date_debut si saisie,
     * sinon date de création du PDP. Format YYYY-MM-DD pour tri chronologique.
     */
    private function extractDatePrefix(Pdp $pdp): string
    {
        $dateDebut = $pdp->data['operation']['date_debut'] ?? null;
        if ($dateDebut) {
            try {
                return \Carbon\Carbon::parse($dateDebut)->format('Y-m-d');
            } catch (\Throwable $e) {}
        }
        return $pdp->created_at->format('Y-m-d');
    }

    /**
     * Sanitize : retire les caractères interdits dans les noms de fichiers
     * (cross-platform : Windows + macOS + Linux).
     */
    private function sanitize(string $s): string
    {
        $s = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|', "\0"], '-', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        return trim($s, ' -');
    }

    /**
     * Construit un nom de ZIP descriptif basé sur les filtres actifs.
     */
    public function buildZipName(array $filters = []): string
    {
        $parts = ['SALTI_PDPs'];
        if (! empty($filters['agency_label'])) $parts[] = $this->sanitize($filters['agency_label']);
        if (! empty($filters['period_label'])) $parts[] = $this->sanitize($filters['period_label']);
        $parts[] = now()->format('Y-m-d');
        return implode('_', $parts).'.zip';
    }
}

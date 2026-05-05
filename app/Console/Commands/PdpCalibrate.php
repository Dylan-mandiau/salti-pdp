<?php

namespace App\Console\Commands;

use App\Services\PdpPdfGenerator;
use Illuminate\Console\Command;

/**
 * Génère un PDF de calibration : croix rouges à chaque coordonnée mappée + label.
 * À ouvrir et superposer mentalement au modèle pour ajuster config/pdp_pdf_mapping.php.
 *
 * Usage : php artisan pdp:calibrate
 */
class PdpCalibrate extends Command
{
    protected $signature = 'pdp:calibrate';
    protected $description = 'Génère un PDF de calibration des coordonnées du mapping PDP';

    public function handle(PdpPdfGenerator $generator): int
    {
        $path = $generator->generateCalibrationPdf();
        $abs = storage_path('app/'.$path);
        $this->info("PDF de calibration généré : $abs");
        $this->info('Ouvre-le et compare visuellement chaque croix rouge à sa zone cible.');
        return self::SUCCESS;
    }
}

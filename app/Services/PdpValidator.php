<?php

namespace App\Services;

use App\Models\Pdp;
use Carbon\Carbon;

/**
 * Moteur de validation/cohérence d'un PDP.
 *
 * Inspecte un PDP et retourne une liste d'alertes classées par sévérité :
 *  - 🔴 'error'   : bloquant — empêche la validation et la signature
 *  - 🟠 'warning' : avertissement — autorise la validation mais signalé
 *  - 🔵 'info'    : information / recommandation
 *
 * Chaque alerte précise le champ concerné et le message à afficher.
 */
class PdpValidator
{
    public const SEVERITY_ERROR = 'error';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_INFO = 'info';

    /** @var array<int, array{severity:string, field:?string, message:string, step:?int}> */
    private array $alerts = [];

    public function check(Pdp $pdp): array
    {
        $this->alerts = [];
        $data = $pdp->data ?? [];

        $this->checkAgenceEtDonneurOrdre($pdp, $data);
        $this->checkEntrepriseExterieure($data);
        $this->checkOperation($data);
        $this->checkInspection($pdp, $data);
        $this->checkSecours($data);
        $this->checkRisques($data);
        $this->checkEpiVsRisques($data);
        $this->checkHabilitationsVsRisques($pdp, $data);
        $this->checkSousTraitance($data);
        $this->checkLocaux($data);
        $this->checkSignatures($pdp, $data);

        return $this->summarize();
    }

    /**
     * Synthèse : true si on peut signer (zéro erreur bloquante).
     */
    public function canSign(Pdp $pdp): bool
    {
        $result = $this->check($pdp);
        return $result['errors_count'] === 0;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Règles de validation
    // ─────────────────────────────────────────────────────────────────────

    private function checkAgenceEtDonneurOrdre(Pdp $pdp, array $data): void
    {
        if (empty($data['eu']['agence'])) {
            $this->error('eu.agence', 'L\'agence SALTI est obligatoire.', 1);
        }
        if (empty($pdp->donneur_ordre_nom) && empty($data['eu']['donneur_ordre'])) {
            $this->error('eu.donneur_ordre', 'Le nom du donneur d\'ordre SALTI est obligatoire.', 1);
        }
        if (empty($data['eu']['phone'])) {
            $this->warning('eu.phone', 'Téléphone de l\'agence non renseigné — utile pour le prestataire.', 1);
        }
    }

    private function checkEntrepriseExterieure(array $data): void
    {
        $ee = $data['ee'] ?? [];

        if (empty($ee['raison_sociale'])) {
            $this->error('ee.raison_sociale', 'La raison sociale de l\'entreprise extérieure est obligatoire.', 1);
        }
        if (empty($ee['responsable_prestations'])) {
            $this->error('ee.responsable_prestations', 'Le responsable des prestations est obligatoire.', 1);
        }
        if (empty($ee['address'])) {
            $this->warning('ee.address', 'Adresse de l\'entreprise extérieure non renseignée.', 1);
        }
        if (empty($ee['phone'])) {
            $this->warning('ee.phone', 'Téléphone du prestataire non renseigné.', 1);
        }
        if (! in_array($ee['sous_traitance'] ?? null, ['oui', 'non'], true)) {
            $this->error('ee.sous_traitance', 'Préciser si les travaux sont sous-traités (Oui / Non).', 1);
        }
    }

    private function checkOperation(array $data): void
    {
        $op = $data['operation'] ?? [];

        if (empty($op['type'])) {
            $this->error('operation.type', 'Type d\'opération obligatoire (Ponctuelle / Annuelle).', 1);
        }
        if (empty($op['volume'])) {
            $this->error('operation.volume', 'Volume horaire obligatoire (< 400h ou > 400h).', 1);
        }
        if (empty($op['designation'])) {
            $this->error('operation.designation', 'Désignation de l\'opération obligatoire.', 1);
        }
        if (empty($op['lieu'])) {
            $this->error('operation.lieu', 'Lieu de l\'opération obligatoire.', 1);
        }
        if (empty($op['date_debut'])) {
            $this->error('operation.date_debut', 'Date de début obligatoire.', 1);
        } else {
            // Date passée ?
            try {
                $date = Carbon::parse($op['date_debut']);
                if ($date->isPast() && ! $date->isToday()) {
                    $this->warning('operation.date_debut', 'La date de début est dans le passé ('.$date->format('d/m/Y').').', 1);
                }
            } catch (\Exception $e) {
                $this->error('operation.date_debut', 'Date de début invalide.', 1);
            }
        }
        if (empty($op['duree'])) {
            $this->warning('operation.duree', 'Durée prévisible non renseignée.', 1);
        }
        if (empty($op['plages_horaires'])) {
            $this->warning('operation.plages_horaires', 'Plages horaires non précisées.', 1);
        }
        if (empty($op['nb_salaries']) || (int)$op['nb_salaries'] < 1) {
            $this->error('operation.nb_salaries', 'Nombre de salariés affectés obligatoire (>=1).', 1);
        }

        // Cohérence : > 400h annuel mais déclaré ponctuel ?
        if (($op['type'] ?? null) === 'ponctuelle' && ($op['volume'] ?? null) === 'plus_400h') {
            $this->warning(null, 'Incohérence : opération marquée Ponctuelle mais Plus de 400 heures.', 1);
        }
    }

    private function checkInspection(Pdp $pdp, array $data): void
    {
        $insp = $data['inspection'] ?? [];
        $op = $data['operation'] ?? [];

        // Inspection commune obligatoire si > 400h (article R4513-1)
        if (($op['volume'] ?? null) === 'plus_400h') {
            if (empty($insp['date'])) {
                $this->error('inspection.date', 'Inspection commune OBLIGATOIRE pour une opération > 400 heures.', 1);
            }
            if (empty($insp['participants'])) {
                $this->error('inspection.participants', 'Participants à l\'inspection commune obligatoires (> 400h).', 1);
            }
        } elseif (empty($insp['date'])) {
            $this->warning('inspection.date', 'Inspection commune fortement recommandée.', 1);
        }

        if (! empty($insp['date'])) {
            try {
                $inspDate = Carbon::parse($insp['date']);
                $opDate = ! empty($op['date_debut']) ? Carbon::parse($op['date_debut']) : null;
                if ($opDate && $inspDate->greaterThan($opDate)) {
                    $this->error('inspection.date', 'L\'inspection commune doit avoir lieu AVANT le début des travaux.', 1);
                }
            } catch (\Exception $e) {
                // ignore
            }
        }
    }

    private function checkSecours(array $data): void
    {
        $sec = $data['secours'] ?? [];
        if (empty($sec['sst_nom'])) {
            $this->warning('secours.sst_nom', 'Nom du SST de l\'agence non renseigné.', 2);
        }
        if (empty($sec['resp_ee_nom'])) {
            $this->warning('secours.resp_ee_nom', 'Nom du responsable EE pour les secours non renseigné.', 2);
        }
    }

    private function checkRisques(array $data): void
    {
        $risques = $data['risques'] ?? [];

        // Au moins "Arrivée sur le site", "Circulation interne" et "Contamination" doivent être cochés (obligatoires sur le PDF officiel)
        $obligatoires = ['arrivee_site', 'circulation_interne', 'contamination'];
        foreach ($obligatoires as $key) {
            if (empty($risques[$key]['applicable'])) {
                $this->error("risques.$key", "Le risque \"$key\" est OBLIGATOIRE selon le PDP officiel SALTI.", 3);
            }
        }

        // Pour chaque risque applicable : au moins un responsable doit être coché (EU ou EE)
        foreach ($risques as $key => $r) {
            if (! empty($r['applicable']) && empty($r['eu']) && empty($r['ee'])) {
                $this->warning("risques.$key", "Risque \"$key\" coché mais aucun responsable (EU / EE) défini.", 3);
            }
        }
    }

    private function checkEpiVsRisques(array $data): void
    {
        $epi = $data['epi'] ?? [];
        $risques = $data['risques'] ?? [];

        // Travail en hauteur → harnais obligatoire
        if (! empty($risques['travail_hauteur']['applicable']) && empty($epi['harnais'])) {
            $this->warning('epi.harnais', 'Travail en hauteur déclaré : EPI Harnais devrait être coché.', 3);
        }

        // Soudure / découpe → lunettes + masque + gants obligatoires
        if (! empty($risques['soudure_decoupe']['applicable'])) {
            if (empty($epi['lunettes'])) {
                $this->warning('epi.lunettes', 'Soudure/découpe déclarée : EPI Lunettes recommandé.', 3);
            }
            if (empty($epi['gants'])) {
                $this->warning('epi.gants', 'Soudure/découpe déclarée : EPI Gants recommandé.', 3);
            }
        }

        // Chantier extérieur (par défaut on suppose) → chaussures + gilet HV obligatoires
        if (empty($epi['chaussures'])) {
            $this->warning('epi.chaussures', 'EPI Chaussures de sécurité fortement recommandées.', 3);
        }
        if (empty($epi['gilet_hv'])) {
            $this->warning('epi.gilet_hv', 'EPI Gilet haute visibilité fortement recommandé sur site SALTI.', 3);
        }
    }

    private function checkHabilitationsVsRisques(Pdp $pdp, array $data): void
    {
        $risques = $data['risques'] ?? [];
        $intervenants = $pdp->intervenants ?? collect();

        // Si risque électrique → au moins un intervenant doit avoir une habilitation B0/B1V/H0V/H2B2/HCBC/BR
        if (! empty($risques['electrique']['applicable'])) {
            $hasHabilitationElec = $intervenants->contains(function ($iv) {
                return preg_match('/(B0|B1V|B2|BR|BC|H0V|H1V|H2B2|HCBC)/i', (string)($iv->habilitation ?? ''));
            });
            if (! $hasHabilitationElec) {
                $this->error(null, 'Risque électrique déclaré mais aucun salarié EE n\'a d\'habilitation électrique enregistrée.', 5);
            }
        }

        // Si levage / nacelle → CACES requis
        if (! empty($risques['levage_manutention']['applicable']) || ! empty($risques['travail_hauteur']['applicable'])) {
            $hasCaces = $intervenants->contains(function ($iv) {
                return stripos((string)($iv->habilitation ?? ''), 'CACES') !== false
                    || stripos((string)($iv->habilitation ?? ''), 'R489') !== false
                    || stripos((string)($iv->habilitation ?? ''), 'R486') !== false;
            });
            if (! $hasCaces) {
                $this->warning(null, 'Levage ou travail en hauteur déclaré mais aucun CACES enregistré chez l\'EE.', 5);
            }
        }

        // Vérifier les dates de validité des habilitations vs durée du PDP
        if (! empty($data['operation']['date_debut'])) {
            try {
                $debut = Carbon::parse($data['operation']['date_debut']);
                foreach ($intervenants as $iv) {
                    if ($iv->habilitation_validity && $iv->habilitation_validity->lt($debut)) {
                        $this->error(null, "Habilitation expirée pour {$iv->nom_prenom} ({$iv->habilitation}) — date de validité {$iv->habilitation_validity->format('d/m/Y')}.", 5);
                    }
                }
            } catch (\Exception $e) {
                // ignore
            }
        }
    }

    private function checkSousTraitance(array $data): void
    {
        if (($data['ee']['sous_traitance'] ?? null) === 'oui') {
            $sousTraitants = $data['ee']['sous_traitants'] ?? [];
            if (empty($sousTraitants) || ! is_array($sousTraitants)) {
                $this->warning('ee.sous_traitants', 'Sous-traitance déclarée : pensez à faire signer un PDP avec chaque sous-traitant.', 1);
            }
        }
    }

    private function checkLocaux(array $data): void
    {
        $locaux = $data['inspection']['locaux'] ?? [];
        if (empty($locaux['vestiaires']) && empty($locaux['sanitaires']) && empty($locaux['refectoire'])) {
            $this->info('inspection.locaux', 'Aucun local social mis à disposition coché — à confirmer si l\'opération dure plusieurs jours.', 1);
        }
    }

    private function checkSignatures(Pdp $pdp, array $data): void
    {
        // Si on est en phase signatures et que SALTI signe en premier : OK
        // Si EE a signé avant SALTI : c'est inhabituel mais autorisé
        if ($pdp->status === Pdp::STATUS_AWAITING_SIGNATURES) {
            if (empty($data['signature_salti_fonction']) && $pdp->signed_by_salti_at) {
                $this->warning(null, 'SALTI a signé mais sa fonction n\'est pas renseignée.', 6);
            }
            if (empty($data['signature_ee_fonction']) && $pdp->signed_by_prestataire_at) {
                $this->warning(null, 'L\'EE a signé mais sa fonction n\'est pas renseignée.', 6);
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────

    private function error(?string $field, string $message, ?int $step = null): void
    {
        $this->alerts[] = ['severity' => self::SEVERITY_ERROR, 'field' => $field, 'message' => $message, 'step' => $step];
    }

    private function warning(?string $field, string $message, ?int $step = null): void
    {
        $this->alerts[] = ['severity' => self::SEVERITY_WARNING, 'field' => $field, 'message' => $message, 'step' => $step];
    }

    private function info(?string $field, string $message, ?int $step = null): void
    {
        $this->alerts[] = ['severity' => self::SEVERITY_INFO, 'field' => $field, 'message' => $message, 'step' => $step];
    }

    private function summarize(): array
    {
        $errors = array_filter($this->alerts, fn($a) => $a['severity'] === self::SEVERITY_ERROR);
        $warnings = array_filter($this->alerts, fn($a) => $a['severity'] === self::SEVERITY_WARNING);
        $infos = array_filter($this->alerts, fn($a) => $a['severity'] === self::SEVERITY_INFO);

        return [
            'alerts' => $this->alerts,
            'errors' => array_values($errors),
            'warnings' => array_values($warnings),
            'infos' => array_values($infos),
            'errors_count' => count($errors),
            'warnings_count' => count($warnings),
            'infos_count' => count($infos),
            'can_sign' => count($errors) === 0,
        ];
    }
}

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
        $this->checkConventionMateriels($data);
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
        $hasDuree = ! empty($op['duree']) || (! empty($op['duree_value']) && ! empty($op['duree_unit']));
        if (! $hasDuree) {
            $this->warning('operation.duree', 'Durée prévisible non renseignée.', 1);
        }
        $hasHoraires = ! empty($op['plages_horaires']) || (! empty($op['plage_debut']) && ! empty($op['plage_fin']));
        if (! $hasHoraires) {
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

        // ━━ ERREURS BLOQUANTES — sécurité obligatoire selon le Code du travail ━━

        // Travail en hauteur → Harnais OBLIGATOIRE (Art. R4323-58 du Code du travail)
        if (! empty($risques['travail_hauteur']['applicable']) && empty($epi['harnais'])) {
            $this->error('epi.harnais', '🪢 Travail en hauteur déclaré → EPI Harnais OBLIGATOIRE (Art. R4323-58 du Code du travail).', 3);
        }

        // Soudure / découpe → Lunettes + Gants + Masque OBLIGATOIRES
        if (! empty($risques['soudure_decoupe']['applicable'])) {
            if (empty($epi['lunettes'])) {
                $this->error('epi.lunettes', '🥽 Soudure/découpe déclarée → EPI Lunettes OBLIGATOIRES (projection particules).', 3);
            }
            if (empty($epi['gants'])) {
                $this->error('epi.gants', '🧤 Soudure/découpe déclarée → EPI Gants OBLIGATOIRES (brûlures, coupures).', 3);
            }
            if (empty($epi['masque'])) {
                $this->error('epi.masque', '😷 Soudure/découpe déclarée → EPI Masque OBLIGATOIRE (fumées, vapeurs).', 3);
            }
        }

        // Levage / manutention → Casque OBLIGATOIRE (chute d\'objets)
        if (! empty($risques['levage_manutention']['applicable']) && empty($epi['casque'])) {
            $this->error('epi.casque', '⛑ Levage/manutention déclaré → EPI Casque OBLIGATOIRE (risque de chute d\'objets).', 3);
        }

        // Produits chimiques → Gants + Lunettes OBLIGATOIRES
        if (! empty($risques['produits_chimiques']['applicable'])) {
            if (empty($epi['gants'])) {
                $this->error('epi.gants', '🧤 Produits chimiques déclarés → EPI Gants OBLIGATOIRES.', 3);
            }
            if (empty($epi['lunettes'])) {
                $this->error('epi.lunettes', '🥽 Produits chimiques déclarés → EPI Lunettes OBLIGATOIRES.', 3);
            }
        }

        // Intervention électrique → Gants OBLIGATOIRES (Norme NF C 18-510)
        if (! empty($risques['electrique']['applicable']) && empty($epi['gants'])) {
            $this->error('epi.gants', '🧤 Intervention électrique déclarée → EPI Gants isolants OBLIGATOIRES (NF C 18-510).', 3);
        }

        // Flexibles d\'engins → Lunettes + Gants OBLIGATOIRES (projections d\'huile)
        if (! empty($risques['flexibles_engins']['applicable'])) {
            if (empty($epi['lunettes'])) {
                $this->error('epi.lunettes', '🥽 Intervention flexibles → EPI Lunettes OBLIGATOIRES (projections d\'huile).', 3);
            }
            if (empty($epi['gants'])) {
                $this->error('epi.gants', '🧤 Intervention flexibles → EPI Gants OBLIGATOIRES.', 3);
            }
        }

        // ━━ EPI BASE SITE SALTI — toujours obligatoires (politique interne) ━━

        if (empty($epi['chaussures'])) {
            $this->error('epi.chaussures', '👟 EPI Chaussures de sécurité OBLIGATOIRES sur tout site SALTI.', 3);
        }
        if (empty($epi['gilet_hv'])) {
            $this->error('epi.gilet_hv', '🦺 EPI Gilet haute visibilité OBLIGATOIRE sur tout site SALTI.', 3);
        }
    }

    private function checkHabilitationsVsRisques(Pdp $pdp, array $data): void
    {
        $risques = $data['risques'] ?? [];
        $intervenants = $pdp->intervenants ?? collect();

        // Récolte tous les codes + labels d'habilitations détenus par les
        // intervenants (depuis habilitations_list qui gère la rétrocompat
        // avec l'ancien champ texte habilitation).
        $codesHeld = [];
        $labelsHeld = '';
        foreach ($intervenants as $iv) {
            foreach ($iv->habilitations_list as $h) {
                if (! empty($h['code'])) $codesHeld[] = $h['code'];
                if (! empty($h['label'])) $labelsHeld .= ' '.$h['label'];
            }
        }
        $codesHeld = array_unique($codesHeld);

        /**
         * Vérifie si l'EE possède au moins une habilitation dans la liste
         * des codes attendus, OU si le label libre matche le regex de fallback.
         */
        $eeHasOneOf = function (array $expectedCodes, string $fallbackRegex) use ($codesHeld, $labelsHeld): bool {
            if (! empty(array_intersect($expectedCodes, $codesHeld))) return true;
            return (bool) preg_match($fallbackRegex, $labelsHeld);
        };

        // ━━ HABILITATIONS OBLIGATOIRES (erreurs bloquantes) ━━

        // Risque électrique → habilitation B0/B1V/H0V/H2B2/HCBC/BR OBLIGATOIRE
        // (Art. R4544-9 Code du travail + Norme NF C 18-510)
        if (! empty($risques['electrique']['applicable'])) {
            $ok = $eeHasOneOf(
                ['B0H0', 'B1V', 'B2V', 'BR', 'BC', 'BE', 'H1V', 'H2V', 'HC'],
                '/(B0|B1V?|B2V?|BR|BC|BE|H0V?|H1V?|H2(B2)?|HC(BC)?)\b/i'
            );
            if (! $ok) {
                $this->error(null, '⚡ Risque électrique déclaré → Habilitation électrique OBLIGATOIRE (B0/B1V/B2V/BR/BC/H1V/H2V/HC) — aucun salarié EE habilité enregistré (Art. R4544-9 Code du travail).', 5);
            }
        }

        // Levage / manutention → CACES OBLIGATOIRE (Art. R4323-55)
        if (! empty($risques['levage_manutention']['applicable'])) {
            $ok = $eeHasOneOf(
                ['R489-1A', 'R489-3', 'R489-5', 'R486-A', 'R486-B', 'R485', 'R484', 'R483', 'R487', 'R490', 'R482-A', 'R482-B1', 'R482-C1', 'R482-F', 'ELINGAGE'],
                '/(CACES|R48[2-7]|R489|R490|élingage|elingueur)/i'
            );
            if (! $ok) {
                $this->error(null, '🏗 Levage/manutention déclaré → CACES OBLIGATOIRE (R486/R489/R484/R483/R487/R490) — aucun CACES enregistré chez l\'EE (Art. R4323-55).', 5);
            }
        }

        // Travail en hauteur → CACES R486 / Harnais / Échafaudages OBLIGATOIRE
        // Distinction importante : l'EPI 'harnais' coché à l'étape 3 ne suffit pas —
        // il faut AUSSI une habilitation/formation au port du harnais pour au moins
        // un salarié EE (Art. R4323-106 Code du travail).
        if (! empty($risques['travail_hauteur']['applicable'])) {
            $ok = $eeHasOneOf(
                ['R486-A', 'R486-B', 'HARNAIS', 'R408', 'R457'],
                '/(R486|nacelle|PEMP|harnais|R408|R457|échafaudage)/i'
            );
            if (! $ok) {
                $epiHarnaisCoche = ! empty($data['epi']['harnais']);
                $msg = '🪜 Travail en hauteur déclaré → habilitation OBLIGATOIRE chez l\'EE : '
                     .'CACES R486 (PEMP), formation Port du harnais, ou montage d\'échafaudage (R408/R457). '
                     .'Aucun salarié EE n\'a d\'habilitation hauteur enregistrée à l\'étape 5.';
                if ($epiHarnaisCoche) {
                    $msg .= ' (l\'EPI « harnais » est coché à l\'étape 3, mais l\'EPI ne remplace pas l\'habilitation au port du harnais — Art. R4323-106).';
                }
                $this->error(null, $msg, 5);
            }
        }

        // Permis feu coché par SALTI → habilitation soudage ou « Permis feu » OBLIGATOIRE
        if (! empty($data['documents_remis_ee']['permis_feu'])) {
            $ok = $eeHasOneOf(
                ['SOUDAGE', 'PERMIS-FEU'],
                '/(soud|EN ?287|ISO ?9606|TIG|MIG|MAG|permis feu)/i'
            );
            if (! $ok) {
                $this->error(null, '🔥 Permis feu requis → Habilitation soudage ou formation Permis feu OBLIGATOIRE pour au moins un salarié EE.', 5);
            }
        }

        // ━━ HABILITATIONS RECOMMANDÉES (warnings non bloquants) ━━

        // Soudure / découpe déclarée → qualification soudeur recommandée
        if (! empty($risques['soudure_decoupe']['applicable'])) {
            $ok = $eeHasOneOf(
                ['SOUDAGE'],
                '/(soud|EN ?287|ISO ?9606|TIG|MIG|MAG)/i'
            );
            if (! $ok) {
                $this->warning(null, '🔥 Soudure/découpe déclarée → Recommandé : qualification soudeur (EN 287 / ISO 9606) chez l\'EE.', 5);
            }
        }

        // Produits chimiques → formation produits chimiques + ATEX recommandés
        if (! empty($risques['produits_chimiques']['applicable'])) {
            $ok = $eeHasOneOf(
                ['CHIMIQUE', 'ATEX-0', 'ATEX-1', 'ATEX-2', 'SS3', 'SS4'],
                '/(produits chimiques|ATEX|amiante|SS3|SS4)/i'
            );
            if (! $ok) {
                $this->warning(null, '🧪 Produits chimiques dangereux → Recommandé : Formation produits chimiques ou ATEX chez l\'EE.', 5);
            }
        }

        // Multi-interventions → Coordination SPS recommandée
        if (! empty($risques['multi_interventions']['applicable'])) {
            $ok = $eeHasOneOf(
                ['SPS'],
                '/(coordination SPS|SPS)/i'
            );
            if (! $ok) {
                $this->warning(null, '🤝 Multi-interventions déclarées → Recommandé : un coordonnateur SPS chez l\'EE.', 5);
            }
        }

        // Circulation interne → permis B/C recommandé si véhicule lourd attendu
        if (! empty($risques['circulation_interne']['applicable'])) {
            $ok = $eeHasOneOf(
                ['PERMIS-B', 'PERMIS-C', 'PERMIS-CE', 'FIMO'],
                '/(permis [BC]|FIMO|FCO)/i'
            );
            if (! $ok) {
                $this->warning(null, '🚛 Circulation interne → Recommandé : permis B/C/CE adapté au véhicule chez l\'EE.', 5);
            }
        }

        // Contamination → SST recommandé
        if (! empty($risques['contamination']['applicable'])) {
            $ok = $eeHasOneOf(
                ['SST', 'SANITAIRE'],
                '/(SST|sauveteur|sanitaire)/i'
            );
            if (! $ok) {
                $this->warning(null, '🦠 Risque contamination → Recommandé : SST (Sauveteur Secouriste du Travail) chez l\'EE.', 5);
            }
        }

        // ━━ DATES DE VALIDITÉ — bloquant si expirée avant l\'intervention ━━

        if (! empty($data['operation']['date_debut'])) {
            try {
                $debut = Carbon::parse($data['operation']['date_debut']);
                foreach ($intervenants as $iv) {
                    foreach ($iv->habilitations_list as $h) {
                        if (! empty($h['validity'])) {
                            try {
                                $exp = Carbon::parse($h['validity']);
                                if ($exp->lt($debut)) {
                                    $this->error(null, "🚫 Habilitation EXPIRÉE pour {$iv->nom_prenom} ({$h['label']}) — validité jusqu'au {$exp->format('d/m/Y')}, intervention prévue le {$debut->format('d/m/Y')}.", 5);
                                }
                            } catch (\Exception $e) {
                                // ignore date format invalide
                            }
                        }
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

    /**
     * Si la case "Convention de prêt" est cochée, au moins 1 matériel doit être listé
     * (sinon la convention serait vide → document inutile).
     */
    private function checkConventionMateriels(array $data): void
    {
        if (! empty($data['documents_remis_ee']['convention_pret'])) {
            $materiels = collect($data['materiels_pretes'] ?? [])
                ->filter(fn($m) => ! empty($m['designation']));
            if ($materiels->isEmpty()) {
                $this->error('materiels_pretes', '🔧 Convention de prêt cochée → liste des matériels prêtés OBLIGATOIRE (étape 2).', 2);
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

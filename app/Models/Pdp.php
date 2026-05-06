<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Pdp extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_AWAITING_PRESTATAIRE = 'awaiting_prestataire';
    public const STATUS_AWAITING_VALIDATION = 'awaiting_validation';
    public const STATUS_CORRECTIONS_REQUESTED = 'corrections_requested';
    public const STATUS_AWAITING_SIGNATURES = 'awaiting_signatures';
    public const STATUS_SIGNED = 'signed';
    public const STATUS_ARCHIVED = 'archived';
    public const STATUS_CANCELLED = 'cancelled';

    public const MODE_PRESENTIEL = 'presentiel';
    public const MODE_DISTANCE = 'distance';

    /**
     * Items du tableau « Mise en sécurité » du Permis feu (PR0103-bis).
     * Slug => libellé affiché. Utilisé à la fois par le formulaire en ligne
     * et le template PDF pour rester en phase.
     */
    public const PERMIS_FEU_MISE_EN_SECURITE = [
        'deplacement_combustibles' => 'Déplacement/Éloignement à plus de 10 mètres des substances combustibles',
        'delimitation_balisage' => 'Délimitation ou séparation et balisage de la zone d\'intervention',
        'protection_objets' => 'Protection des éléments et/ou objets n\'ayant pas pu être déplacés',
        'consignation' => 'Consignation (source d\'énergie, flux de produit...)',
        'vidange_nettoyage' => 'Vidange – nettoyage – dépoussiérage',
        'degazage' => 'Dégazage (tuyauterie, cuve, citerne...)',
        'remplissage_inertage' => 'Remplissage/inertage (eau, gaz…)',
        'isolation_tuyauteries' => 'Isolation des tuyauteries',
        'demontage_tuyauterie' => 'Démontage de tuyauterie',
        'colmatage_interstices' => 'Colmatage des interstices',
        'fermeture' => 'Fermeture (appareil, caniveaux, fosses...)',
        'isolation_detection' => 'Isolation de la boucle de détection',
        'isolation_extinction' => 'Isolation du système d\'extinction',
        'modification_atex' => 'Modification du zonage ATEX existant suite aux mesures de mise en sécurité prises',
    ];

    /**
     * Items du tableau « Moyens de prévention » du Permis feu.
     */
    public const PERMIS_FEU_MOYENS_PREVENTION = [
        'protection_abords' => 'Protection des abords (écrans, panneaux, bâches ignifugées, eau, sable, absorbant)',
        'ventilation' => 'Ventilation mécanique forcée',
        'controle_atmosphere' => 'Contrôle d\'atmosphère (explosimétrie, teneur en oxygène, détecteur de gaz)',
        'lutte_incendie' => 'Moyens de lutte contre l\'incendie (extincteur, RIA, lance à incendie)',
        'materiel_atex' => 'Utilisation de matériel spécifique pour travailler en zone ATEX',
    ];

    protected $fillable = [
        'uuid',
        'agency_id',
        'prestataire_id',
        'mode',
        'status',
        'donneur_ordre_nom',
        'require_otp',
        'magic_token',
        'magic_token_expires_at',
        'data',
        'sent_to_prestataire_at',
        'submitted_by_prestataire_at',
        'validated_by_salti_at',
        'signed_by_salti_at',
        'signed_by_prestataire_at',
        'cancelled_at',
        'cancelled_reason',
        'final_pdf_path',
        'final_pdf_sha256',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'require_otp' => 'boolean',
            'agency_id' => 'integer',         // garantit la comparaison stricte avec auth()->id()
            'prestataire_id' => 'integer',
            'magic_token_expires_at' => 'datetime',
            'sent_to_prestataire_at' => 'datetime',
            'submitted_by_prestataire_at' => 'datetime',
            'validated_by_salti_at' => 'datetime',
            'signed_by_salti_at' => 'datetime',
            'signed_by_prestataire_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $pdp) {
            $pdp->uuid ??= (string) Str::uuid();
            $pdp->data ??= self::emptyData();
        });
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agency_id');
    }

    public function prestataire(): BelongsTo
    {
        return $this->belongsTo(Prestataire::class);
    }

    public function intervenants(): HasMany
    {
        return $this->hasMany(PdpIntervenant::class);
    }

    /**
     * Si les 2 parties (SALTI + presta) ont signé, marque le PDP comme SIGNED,
     * génère le PDF final et stocke son hash SHA-256.
     *
     * Idempotent : appelable depuis n'importe quel endpoint de signature
     * (signSalti, signEePresentiel, prestataire.sign) sans risque de doublons.
     *
     * Retourne true si le PDP vient d'être finalisé, false sinon.
     */
    public function finalizeIfBothSigned(\App\Services\PdpHtmlPdfGenerator $generator): bool
    {
        $this->refresh();
        if (! $this->signed_by_salti_at || ! $this->signed_by_prestataire_at) {
            return false;
        }
        if ($this->status === self::STATUS_SIGNED || $this->status === self::STATUS_ARCHIVED) {
            return false;
        }

        // Backfill du permis feu pour les PDP déjà signés avant l'auto-sync :
        // si le permis feu est requis et que la signature/date de délivrance
        // sont vides, on les copie depuis la signature presta.
        $data = $this->data;
        $changed = false;
        if (! empty($data['documents_remis_ee']['permis_feu'])) {
            if (empty($data['permis_feu']['signed_by_employer']) && ! empty($data['signature_ee'])) {
                $data['permis_feu']['signed_by_employer'] = $data['signature_ee'];
                $changed = true;
            }
            if (empty($data['permis_feu']['date_delivrance']) && $this->signed_by_prestataire_at) {
                $data['permis_feu']['date_delivrance'] = $this->signed_by_prestataire_at->toDateString();
                $changed = true;
            }
        }
        if ($changed) {
            $this->data = $data;
            $this->save();
        }

        $finalPath = $generator->generate($this);
        $absolutePath = storage_path('app/'.$finalPath);
        $hash = hash_file('sha256', $absolutePath);

        $this->update([
            'status' => self::STATUS_SIGNED,
            'final_pdf_path' => $finalPath,
            'final_pdf_sha256' => $hash,
        ]);

        return true;
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PdpDocument::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(PdpAuditLog::class)->orderByDesc('created_at');
    }

    public function generateMagicToken(int $validityDays = 7): string
    {
        $token = Str::random(64);
        $this->update([
            'magic_token' => $token,
            'magic_token_expires_at' => now()->addDays($validityDays),
        ]);
        return $token;
    }

    public function magicLinkUrl(): string
    {
        return url('/p/'.$this->magic_token);
    }

    /**
     * Squelette des données du formulaire (mappé sur les 6 pages du PDF officiel).
     * Stocké en JSON dans la colonne `data`.
     */
    public static function emptyData(): array
    {
        return [
            // Page 1 - Entreprise utilisatrice (SALTI)
            'eu' => [
                'agence' => null,
                'donneur_ordre' => null,
                'address' => null,
                'phone' => null,
            ],
            // Page 1 - Entreprise extérieure (EE)
            'ee' => [
                'raison_sociale' => null,
                'responsable_prestations' => null,
                'address' => null,
                'phone' => null,
                'siret' => null,                  // utilisé pour la convention de prêt
                'sous_traitance' => null,        // 'oui' | 'non'
                'sous_traitants' => [],
            ],
            // Page 1 - Nature de l'opération
            'operation' => [
                'type' => null,                  // 'ponctuelle' | 'annuelle'
                'volume' => null,                // 'moins_400h' | 'plus_400h'
                'travaux_dangereux' => false,    // arrêté du 19/03/93
                'designation' => null,
                'lieu' => null,
                'date_debut' => null,
                'duree' => null,
                'plages_horaires' => null,
                'nb_salaries' => null,
            ],
            // Page 1 - Inspection commune
            'inspection' => [
                'date' => null,
                'participants' => null,
                'informations_echangees' => null,
                'zones_visitees' => null,
                'observations_cssct' => null,
                'locaux' => [
                    'vestiaires' => false,
                    'sanitaires' => false,
                    'refectoire' => false,
                ],
            ],
            // Page 2 - Documents échangés
            'documents_remis_ee' => [
                'plan_acces' => false,
                'permis_feu' => false,
                'convention_pret' => false,
            ],
            'documents_remis_salti' => [
                'autorisation_conduite' => false,
                'caces' => false,
                'habilitations' => false,
            ],
            // Page 2 - Organisation des secours
            'secours' => [
                'sst_nom' => null,
                'sst_fonction' => null,
                'resp_ee_nom' => null,
                'resp_ee_fonction' => null,
            ],
            // Page 3 - EPI obligatoires (cases cochées)
            'epi' => [
                'chaussures' => true,
                'gants' => false,
                'casque' => false,
                'lunettes' => false,
                'masque' => false,
                'auditives' => false,
                'gilet_hv' => true,
                'harnais' => false,
                'autres' => null,
            ],
            // Pages 3-5 - Matrice des risques (chaque ligne : applicable + responsabilité EU/EE)
            'risques' => [
                'arrivee_site' => ['applicable' => true, 'eu' => false, 'ee' => true],
                'circulation_interne' => ['applicable' => true, 'eu' => true, 'ee' => true],
                'stationnement' => ['applicable' => false, 'eu' => false, 'ee' => true],
                'sols_souilles' => ['applicable' => false, 'eu' => false, 'ee' => true],
                'travail_hauteur' => ['applicable' => false, 'eu' => false, 'ee' => true],
                'levage_manutention' => ['applicable' => false, 'eu' => false, 'ee' => true],
                'soudure_decoupe' => ['applicable' => false, 'eu' => false, 'ee' => true],
                'dechets' => ['applicable' => false, 'eu' => false, 'ee' => true],
                'electrique' => ['applicable' => false, 'eu' => true, 'ee' => true],
                'produits_chimiques' => ['applicable' => false, 'eu' => false, 'ee' => true],
                'flexibles_engins' => ['applicable' => false, 'eu' => false, 'ee' => true],
                'multi_interventions' => ['applicable' => false, 'eu' => false, 'ee' => true],
                'contamination' => ['applicable' => true, 'eu' => true, 'ee' => true],
            ],
            // Page 5 - Autres risques (libre)
            'autres_risques' => [],
            // Page 6 - Signatures (stockées comme dataURL PNG)
            'signature_salti' => null,
            'signature_ee' => null,
            'signature_salti_fonction' => null,
            'signature_ee_fonction' => null,
            // Matériels prêtés (si Convention cochée)
            'materiels_pretes' => [],
            // Permis feu (rempli par le prestataire, soit en ligne soit imprimé/scanné)
            'permis_feu' => [
                'mode_remplissage' => null,    // 'online' | 'paper'
                'mode_operatoire' => null,
                'operateurs_autorises' => null,
                'travaux' => [
                    'soudage' => false, 'tronconnage' => false,
                    'decoupage' => false, 'meulage' => false, 'autre' => null,
                ],
                'materiels' => [
                    'poste_souder' => false, 'chalumeau' => false,
                    'laser' => false, 'tronconneuse' => false, 'autre' => null,
                ],
                'risques_particuliers' => null,
                'zone_atex_presence' => false,
                'zone_atex_proximite' => false,
                'zone_atex_details' => null,
                'documents_associes' => [
                    'autorisation_travail' => false, 'permis_penetrer' => false,
                    'drpce' => false, 'certificat_degazage' => false,
                ],
                'surveillance_pendant' => null,
                'surveillance_pendant_visa' => null,
                'surveillance_apres_de' => null,
                'surveillance_apres_a' => null,
                'surveillance_apres_nom' => null,
                'surveillance_apres_visa' => null,
                'alerte_emplacement' => null,
                'pompiers_tel' => '18',
                'contact_accident_nom' => null,
                'contact_accident_tel' => null,
                'date_delivrance' => null,
                'signed_by_employer' => null, // dataURL PNG
                // Tableaux Mise en sécurité / Moyens de prévention :
                // chaque ligne = ['a_faire' => 'oui'|'non'|null, 'qui' => str, 'fait' => 'oui'|'non'|null, 'fait_le' => 'YYYY-MM-DD'|null]
                'mise_en_securite' => array_fill_keys(
                    array_keys(self::PERMIS_FEU_MISE_EN_SECURITE),
                    ['a_faire' => null, 'qui' => null, 'fait' => null, 'fait_le' => null]
                ),
                'moyens_prevention' => array_fill_keys(
                    array_keys(self::PERMIS_FEU_MOYENS_PREVENTION),
                    ['a_faire' => null, 'qui' => null, 'fait' => null, 'fait_le' => null]
                ),
            ],
        ];
    }
}

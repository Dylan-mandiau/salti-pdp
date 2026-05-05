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
        ];
    }
}

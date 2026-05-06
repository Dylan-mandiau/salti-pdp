<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PdpIntervenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'pdp_id',
        'nom_prenom',
        'date_signature',
        'signature_data',
        'habilitation',
        'habilitation_validity',
        'habilitations',
        'is_representant',
    ];

    protected function casts(): array
    {
        return [
            'date_signature' => 'date',
            'habilitation_validity' => 'date',
            'habilitations' => 'array',
            'is_representant' => 'boolean',
        ];
    }

    public function pdp(): BelongsTo
    {
        return $this->belongsTo(Pdp::class);
    }

    /**
     * Liste normalisée des habilitations du salarié.
     * Rétrocompat : si habilitations (JSON) est null mais habilitation
     * (string) est rempli, on retourne une liste à 1 élément.
     *
     * @return array<int, array{code: string|null, label: string, validity: string|null}>
     */
    public function getHabilitationsListAttribute(): array
    {
        if (! empty($this->habilitations) && is_array($this->habilitations)) {
            return collect($this->habilitations)->map(fn($h) => [
                'code' => $h['code'] ?? null,
                'label' => $h['label'] ?? '',
                'validity' => $h['validity'] ?? null,
            ])->filter(fn($h) => ! empty($h['label']))->values()->all();
        }
        if (! empty($this->habilitation)) {
            return [[
                'code' => null,
                'label' => $this->habilitation,
                'validity' => $this->habilitation_validity?->format('Y-m-d'),
            ]];
        }
        return [];
    }
}

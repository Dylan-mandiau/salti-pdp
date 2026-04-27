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
    ];

    protected function casts(): array
    {
        return [
            'date_signature' => 'date',
            'habilitation_validity' => 'date',
        ];
    }

    public function pdp(): BelongsTo
    {
        return $this->belongsTo(Pdp::class);
    }
}

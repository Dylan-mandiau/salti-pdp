<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prestataire extends Model
{
    use HasFactory;

    protected $fillable = [
        'agency_id',
        'raison_sociale',
        'responsable_nom',
        'email',
        'phone',
        'address',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agency_id');
    }

    public function pdps(): HasMany
    {
        return $this->hasMany(Pdp::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PdpDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'pdp_id',
        'type',
        'label',
        'path',
        'original_filename',
        'mime_type',
        'size',
        'uploaded_by',
    ];

    public function pdp(): BelongsTo
    {
        return $this->belongsTo(Pdp::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PdpAuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'pdp_id',
        'actor',
        'action',
        'payload',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function pdp(): BelongsTo
    {
        return $this->belongsTo(Pdp::class);
    }
}

<?php

namespace App\Models;

use App\Models\User;
use App\Models\Company;
use App\Models\Signatory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QrStamp extends Model
{
    /** @use HasFactory<\Database\Factories\QrStampFactory> */
    use HasFactory,SoftDeletes;

    protected $keyType = 'int';
    public $incrementing = true;

    protected $fillable = [
        'unique_code',
        'company_id',
        'signatory_id',
        'payload_encrypted',
        'signature_hash',
        'qr_image_path',
        'status',
        'issued_at',
        'expires_at',
        'revoked_at',
        'revocation_reason',
        'metadata',
        'created_by',
         'updated_by',

    ];

    protected $guarded = [
        'verification_count',
        'last_verified_at',
        'deleted_by',
        'created_at',
        'updated_at',
        'deleted_at',

    ];

      protected $casts = [
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'verification_count' => 'integer',
        'last_verified_at' => 'datetime',
        'metadata' => 'array',
        'deleted_at' => 'datetime',
    ];


    // Relations

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function signatory(): BelongsTo
    {
        return $this->belongsTo(Signatory::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

     public function isActive(): bool
    {
        return $this->status === 'active' 
            && (is_null($this->expires_at) || $this->expires_at->isFuture());
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isRevoked(): bool
    {
        return $this->status === 'revoked';
    }

    public function incrementVerification(): void
    {
        $this->increment('verification_count');
        $this->update(['last_verified_at' => now()]);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function canBeVerified(): bool
    {
        return $this->isActive() && ! $this->isRevoked();
    }
}

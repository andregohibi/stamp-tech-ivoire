<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QrStamp extends Model
{
    /** @use HasFactory<\Database\Factories\QrStampFactory> */
    use HasFactory,SoftDeletes;

    protected $keyType = 'int';
    public $incrementing = true;

    protected $fillable = [
         'payload_encrypted',
        'signature_hash',
        'qr_image_path',
        'status',
        'issued_at',
        'expires_at',
        'revoked_at',
        'revocation_reason',
        'metadata',

    ];

    protected $guarded = [
        'unique_code',
        'company_id',
        'signatory_id',
        'verification_count',
        'last_verified_at',
        'created_by',
        'updated_by',
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

}

<?php

namespace App\Models;

use App\Models\User;
use App\Models\Company;
use App\Models\QrStamp;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Signatory extends Model
{
    /** @use HasFactory<\Database\Factories\SignatoryFactory> */
    use HasFactory, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

     protected $fillable = [
        'company_id',
        'first_name',
        'last_name',
        'position',
        'department',
        'phone',
        'email',
        'address',
        'signature_image',
        'status',
        'can_generate_qr',
        'notes',
    ];

    protected $casts = [
        'can_generate_qr' => 'boolean',
        'status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string)Str::uuid();
            }
        });
    }

    //Relations
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

     /**
     * Un signataire a UN SEUL QR code
     */
    public function qrStamp(): HasOne
    {
        return $this->hasOne(QrStamp::class);
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


    //les methodes

    public function getFullNameAttribute():string
    {
        return "{$this->first_name} {$this->last_name}";
    }

     public function hasQrStamp(): bool
    {
        return $this->qrStamp()->exists();
    }


    public function hasActiveQrStamp(): bool
    {
        $qr = $this->qrStamp()->first();

        if (! $qr) {
            return false;
        }

        return method_exists($qr, 'isActive') ? $qr->isActive() : ($qr->status === 'active' && (is_null($qr->expires_at) || $qr->expires_at > now()));
    }

   
}

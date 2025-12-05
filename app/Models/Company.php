<?php

namespace App\Models;

use App\Models\User;
use App\Models\QrStamp;
use App\Models\Signatory;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Company extends Model
{
    /** @use HasFactory<\Database\Factories\CompanyFactory> */
    use HasFactory , SoftDeletes;

    protected $table = 'companies';

    protected $dates = ['deleted_at' , 'subscription_expires_at' , 'created_at' , 'updated_at'];

    public $incrementing = false;

    protected $keyType = "string";


    protected $fillable = [
        'name',
        'legal_name',
        'registration_number',
        'sector',
        'address',
        'city',
        'country',
        'phone',
        'email',
        'logo',
        'website',
        'status',
        'subscription_type',
        'subscription_expires_at',
        'qr_quota',
        'notes',
       
    ];
    

    protected $guarded = [
        'id',
        'qr_used',
        'created_at',
        'updated_at',
        'deleted_at',
        'created_by'
    ];

    protected function casts(): array
    {
        return [
            'subscription_expires_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

     protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string)Str::uuid();
            }
        });
    }

    //Relations entre les tables

    public function signatories(): HasMany
    {
        return $this->hasMany(Signatory::class);

    }

     /**
     * Une entreprise a plusieurs QR stamps (un par signataire)
     */
    public function qrStamps(): HasMany
    {
        return $this->hasMany(QrStamp::class);
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

      public function isSubscriptionExpired(): bool
    {
        return $this->subscription_expires_at && $this->subscription_expires_at->isPast();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Vérifier si l'entreprise a atteint son quota de QR codes
     */
    public function hasReachedQrQuota(): bool
    {
        return $this->qr_used >= $this->qr_quota;
    }

    /**
     * Vérifier si l'entreprise peut générer un QR code
     */
    public function canGenerateQr(): bool
    {
        return $this->isActive() 
            && !$this->isSubscriptionExpired() 
            && !$this->hasReachedQrQuota();
    }

    /**
     * Incrémenter le compteur de QR codes utilisés
     */
    public function incrementQrUsed(): void
    {
        $this->increment('qr_used');
    }

    /**
     * Obtenir le nombre de QR codes restants
     */
    public function getRemainingQrQuota(): int
    {
        return max(0, $this->qr_quota - $this->qr_used);
    }

    /**
     * Obtenir les signataires avec QR code actif
     */
    public function signatoriesWithActiveQr()
    {
        return $this->signatories()
            ->whereHas('qrStamp', function ($query) {
                $query->active();
            });
    }



}

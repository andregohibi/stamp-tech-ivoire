<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
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


   
}

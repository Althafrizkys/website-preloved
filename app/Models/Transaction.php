<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    protected $table = 'transactions';

    protected $primaryKey = 'transaction_id';

    public $timestamps = true;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = null;

    protected $fillable = [
        'buyer_id',
        'seller_id',
        'meeting_point_id',
        'subtotal_price',
        'service_fee',
        'agreed_discount',
        'total_price',
        'payment_method',
        'transaction_status',
        'buyer_notes',
        'scheduled_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_price' => 'decimal:2',
            'service_fee' => 'decimal:2',
            'agreed_discount' => 'decimal:2',
            'total_price' => 'decimal:2',
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id', 'user_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id', 'user_id');
    }

    public function meetingPoint(): BelongsTo
    {
        return $this->belongsTo(CampusMeetingPoint::class, 'meeting_point_id', 'meeting_point_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class, 'transaction_id', 'transaction_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->transaction_status) {
            'menunggu' => 'Menunggu Konfirmasi',
            'dijadwalkan' => 'Dijadwalkan COD',
            'selesai' => 'Selesai',
            'dibatalkan' => 'Dibatalkan',
            default => ucfirst($this->transaction_status),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->transaction_status) {
            'menunggu' => 'bg-[#fff4d8] text-[#8a6500]',
            'dijadwalkan' => 'bg-[#e0f2fe] text-[#0369a1]',
            'selesai' => 'bg-[#e6f4f0] text-[#236a5a]',
            'dibatalkan' => 'bg-[#fee2e2] text-[#991b1b]',
            default => 'bg-[#f0f3f6] text-[#526170]',
        };
    }
}

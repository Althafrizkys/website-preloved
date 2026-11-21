<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampusMeetingPoint extends Model
{
    protected $table = 'campus_meeting_points';

    protected $primaryKey = 'meeting_point_id';

    public $timestamps = false;

    protected $fillable = [
        'campus',
        'point_name',
        'address_description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'meeting_point_id', 'meeting_point_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForCampus(Builder $query, string $campus): Builder
    {
        return $query->where('campus', $campus);
    }
}

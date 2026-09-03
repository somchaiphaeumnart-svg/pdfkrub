<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'display_name_th',
        'price_monthly',
        'price_yearly',
        'currency',
        'features',
        'max_file_size_mb',
        'daily_conversions',
        'file_retention_hours',
        'has_ocr',
        'has_esign',
        'has_watermark',
        'has_api_access',
        'max_team_members',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'features' => 'array',
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'has_ocr' => 'boolean',
        'has_esign' => 'boolean',
        'has_watermark' => 'boolean',
        'has_api_access' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function isFree(): bool
    {
        return $this->name === 'free';
    }

    public function hasUnlimitedConversions(): bool
    {
        return $this->daily_conversions === -1;
    }

    public function getMaxFileSizeBytes(): int
    {
        return $this->max_file_size_mb * 1024 * 1024;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}

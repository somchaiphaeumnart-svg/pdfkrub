<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'is_admin',
        'password',
        'avatar_url',
        'provider',
        'provider_id',
        'locale',
        'storage_used',
        'plan_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'storage_used' => 'integer',
            'is_admin' => 'boolean',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->active();
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->active();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function uploadedFiles(): HasMany
    {
        return $this->hasMany(UploadedFile::class);
    }

    public function pdfJobs(): HasMany
    {
        return $this->hasMany(PdfJob::class);
    }

    public function usageLogs(): HasMany
    {
        return $this->hasMany(UsageLog::class);
    }

    public function getActivePlan(): Plan
    {
        return $this->plan ?? Plan::where('name', 'free')->first();
    }

    public function hasActivePremiumSubscription(): bool
    {
        return $this->subscription !== null && $this->subscription->isActive();
    }

    public function canUploadFile(int $fileSizeBytes): bool
    {
        $plan = $this->getActivePlan();

        return $fileSizeBytes <= $plan->getMaxFileSizeBytes();
    }

    public function getRemainingDailyConversions(?string $toolName = null): int
    {
        $plan = $this->getActivePlan();

        if ($plan->hasUnlimitedConversions()) {
            return PHP_INT_MAX;
        }

        $used = $this->pdfJobs()
            ->whereDate('created_at', today())
            ->where('status', '!=', PdfJob::STATUS_FAILED)
            ->count();

        return max(0, $plan->daily_conversions - $used);
    }

    public function isOAuthUser(): bool
    {
        return $this->provider !== null && $this->provider !== 'email';
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }
}

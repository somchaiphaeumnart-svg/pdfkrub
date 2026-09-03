<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class UploadedFile extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'session_id',
        'original_name',
        'storage_key',
        'storage_disk',
        'file_size',
        'mime_type',
        'file_hash',
        'page_count',
        'metadata',
        'is_encrypted',
        'encryption_key_id',
        'is_scanned',
        'is_clean',
        'expires_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_encrypted' => 'boolean',
        'is_scanned' => 'boolean',
        'is_clean' => 'boolean',
        'expires_at' => 'datetime',
        'file_size' => 'integer',
        'page_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTemporaryUrl(int $minutesTtl = 60): string
    {
        // S3-compatible disks support presigned URLs
        if ($this->storage_disk !== 'local') {
            return Storage::disk($this->storage_disk)
                ->temporaryUrl($this->storage_key, now()->addMinutes($minutesTtl));
        }

        // Local disk: return a signed download route
        return url()->signedRoute(
            'files.download',
            ['file' => $this->id],
            now()->addMinutes($minutesTtl)
        );
    }

    public function getFileSizeForHumans(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return round($bytes, 2).' '.$units[$index];
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')->where('expires_at', '<', now());
    }

    public function scopeForSession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }
}

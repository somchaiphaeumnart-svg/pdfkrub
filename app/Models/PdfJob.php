<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PdfJob extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'user_id',
        'session_id',
        'input_file_ids',
        'output_file_id',
        'tool_name',
        'tool_config',
        'status',
        'progress',
        'error_message',
        'queue_name',
        'queue_job_id',
        'processing_time_ms',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'input_file_ids' => 'array',
        'tool_config' => 'array',
        'progress' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Status constants
    public const STATUS_QUEUED = 'queued';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_DONE = 'done';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function outputFile(): BelongsTo
    {
        return $this->belongsTo(UploadedFile::class, 'output_file_id');
    }

    public function isComplete(): bool
    {
        return $this->status === self::STATUS_DONE;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isPending(): bool
    {
        return in_array($this->status, [self::STATUS_QUEUED, self::STATUS_PROCESSING]);
    }

    public function markAsProcessing(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'started_at' => now(),
            'progress' => 0,
        ]);
    }

    public function markAsComplete(string $outputFileId): void
    {
        $startedAt = $this->started_at ?? now();

        $this->update([
            'status' => self::STATUS_DONE,
            'output_file_id' => $outputFileId,
            'progress' => 100,
            'completed_at' => now(),
            'processing_time_ms' => now()->diffInMilliseconds($startedAt),
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_QUEUED, self::STATUS_PROCESSING]);
    }

    public function scopeForSession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RawSignal extends Model
{
    /** @use HasFactory<\Database\Factories\RawSignalFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'ingestion_run_id',
        'source',
        'source_id',
        'source_url',
        'title',
        'content',
        'author',
        'score',
        'comment_count',
        'category',
        'metadata',
        'processed',
        'flagged',
        'published_at',
    ];

    public function ingestionRun(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(IngestionRun::class);
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'processed' => 'boolean',
            'flagged' => 'boolean',
            'published_at' => 'datetime',
            'score' => 'integer',
            'comment_count' => 'integer',
        ];
    }
}

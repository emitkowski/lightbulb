<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Database\Factories\IngestionRunFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IngestionRun extends Model
{
    /** @use HasFactory<IngestionRunFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'source',
        'query',
        'signals_found',
        'signals_inserted',
        'signals_skipped',
        'status',
        'error_message',
        'duration_ms',
    ];

    public function signals(): HasMany
    {
        return $this->hasMany(RawSignal::class);
    }

    protected function casts(): array
    {
        return [
            'signals_found' => 'integer',
            'signals_inserted' => 'integer',
            'signals_skipped' => 'integer',
            'duration_ms' => 'integer',
        ];
    }
}

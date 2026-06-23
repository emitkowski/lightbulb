<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdeaSignal extends Model
{
    use HasUuids;

    protected $fillable = ['idea_id', 'raw_signal_id', 'weight'];

    protected function casts(): array
    {
        return ['weight' => 'decimal:2'];
    }

    public function idea(): BelongsTo
    {
        return $this->belongsTo(Idea::class);
    }

    public function rawSignal(): BelongsTo
    {
        return $this->belongsTo(RawSignal::class);
    }
}

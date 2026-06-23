<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Idea extends Model
{
    /** @use HasFactory<\Database\Factories\IdeaFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'title',
        'description',
        'signals_summary',
        'source_signals_count',
        'specificity_gate_status',
        'specificity_gate_answers',
        'specificity_gate_reasoning',
        'competition_query',
        'competition_results',
        'competition_summary',
        'score_problem_strength',
        'score_distribution_path',
        'score_competition_gap',
        'score_build_feasibility',
        'score_automability',
        'score_revenue_plausibility',
        'score_overall',
        'score_reasoning',
        'kill_condition',
        'kill_reasoning',
        'success_pattern_confidence',
        'success_pattern_notes',
        'status',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'specificity_gate_answers' => 'array',
            'competition_results' => 'array',
            'score_reasoning' => 'array',
            'score_problem_strength' => 'integer',
            'score_distribution_path' => 'integer',
            'score_competition_gap' => 'integer',
            'score_build_feasibility' => 'integer',
            'score_automability' => 'integer',
            'score_revenue_plausibility' => 'integer',
            'score_overall' => 'integer',
            'success_pattern_confidence' => 'integer',
            'source_signals_count' => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    public function signals(): BelongsToMany
    {
        return $this->belongsToMany(RawSignal::class, 'idea_signals')
            ->withPivot('weight')
            ->withTimestamps();
    }

    public function ideaSignals(): HasMany
    {
        return $this->hasMany(IdeaSignal::class);
    }

    public function isScored(): bool
    {
        return $this->status === 'scored';
    }

    public function isDiscarded(): bool
    {
        return in_array($this->status, ['gate_failed', 'discarded']);
    }
}

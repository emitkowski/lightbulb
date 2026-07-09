<?php

namespace App\Models;

use Database\Factories\SuccessPatternFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuccessPattern extends Model
{
    /** @use HasFactory<SuccessPatternFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'product_name',
        'revenue_milestone',
        'mrr_amount',
        'category',
        'description',
        'pain_solved',
        'target_customer',
        'pricing_model',
        'key_insight',
        'source_url',
        'source',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'mrr_amount' => 'integer',
            'metadata' => 'array',
        ];
    }
}

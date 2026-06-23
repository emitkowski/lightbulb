<div class="space-y-6 p-2">

    {{-- Score summary --}}
    @if($idea->score_overall !== null)
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
        @foreach([
            'Problem Strength' => $idea->score_problem_strength,
            'Distribution Path' => $idea->score_distribution_path,
            'Competition Gap' => $idea->score_competition_gap,
            'Build Feasibility' => $idea->score_build_feasibility,
            'Automability' => $idea->score_automability,
            'Revenue Plausibility' => $idea->score_revenue_plausibility,
        ] as $label => $score)
        <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-3 text-center">
            <div class="text-2xl font-bold {{ $score >= 75 ? 'text-success-600' : ($score >= 60 ? 'text-warning-600' : 'text-danger-600') }}">
                {{ $score ?? '—' }}
            </div>
            <div class="text-xs text-gray-500 mt-1">{{ $label }}</div>
        </div>
        @endforeach
    </div>

    <div class="text-center py-3 border-t border-b border-gray-200 dark:border-gray-700">
        <span class="text-4xl font-black {{ $idea->score_overall >= 75 ? 'text-success-600' : ($idea->score_overall >= 60 ? 'text-warning-600' : 'text-danger-600') }}">
            {{ $idea->score_overall }}
        </span>
        <span class="text-gray-400 text-lg">/100</span>
        <div class="text-sm text-gray-500 mt-1">Overall score</div>
    </div>
    @endif

    {{-- Signals summary --}}
    @if($idea->signals_summary)
    <div>
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Signal Summary ({{ $idea->source_signals_count }} signals)</h3>
        <div class="text-sm text-gray-600 dark:text-gray-400 whitespace-pre-wrap bg-gray-50 dark:bg-gray-800 rounded p-3">{{ $idea->signals_summary }}</div>
    </div>
    @endif

    {{-- Specificity gate --}}
    @if($idea->specificity_gate_answers)
    <div>
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Specificity Gate</h3>
        <dl class="space-y-2 text-sm">
            @foreach([
                'Day-one action' => $idea->specificity_gate_answers['day_one_action'] ?? null,
                'vs. free tools' => $idea->specificity_gate_answers['free_tool_comparison'] ?? null,
                'First customer' => $idea->specificity_gate_answers['first_paying_customer'] ?? null,
                'Switch reason' => $idea->specificity_gate_answers['competitor_switch_reason'] ?? null,
            ] as $label => $answer)
            @if($answer)
            <div>
                <dt class="font-medium text-gray-500">{{ $label }}</dt>
                <dd class="text-gray-700 dark:text-gray-300 mt-0.5">{{ $answer }}</dd>
            </div>
            @endif
            @endforeach
        </dl>
    </div>
    @endif

    {{-- Per-dimension reasoning --}}
    @if($idea->score_reasoning)
    <div>
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Scoring Reasoning</h3>
        <dl class="space-y-2 text-sm">
            @foreach($idea->score_reasoning as $dimension => $text)
            <div>
                <dt class="font-medium text-gray-500">{{ str($dimension)->replace('_', ' ')->title() }}</dt>
                <dd class="text-gray-700 dark:text-gray-300 mt-0.5">{{ $text }}</dd>
            </div>
            @endforeach
        </dl>
    </div>
    @endif

    {{-- Competition --}}
    @if($idea->competition_summary)
    <div>
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Competition Search</h3>
        <div class="text-sm text-gray-600 dark:text-gray-400 whitespace-pre-wrap bg-gray-50 dark:bg-gray-800 rounded p-3">{{ $idea->competition_summary }}</div>
    </div>
    @endif

    {{-- Success pattern --}}
    @if($idea->success_pattern_notes)
    <div>
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
            Pattern Confidence: {{ $idea->success_pattern_confidence }}%
        </h3>
        <div class="text-sm text-gray-600 dark:text-gray-400">{{ $idea->success_pattern_notes }}</div>
    </div>
    @endif

</div>

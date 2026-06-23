<?php

namespace App\Services\Scoring;

use App\Models\Idea;
use App\Models\SuccessPattern;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ScoringAgentService
{
    /**
     * Run the Specificity Gate (Section 6b).
     *
     * @return array{passed: bool, answers: array<string, string>, reasoning: string}
     */
    public function runSpecificityGate(Idea $idea): array
    {
        if (! $this->hasApiKey()) {
            return $this->stubbedGateResult();
        }

        $prompt = $this->buildGatePrompt($idea);

        try {
            $response = $this->callClaude($prompt, maxTokens: 800);

            return $this->parseGateResponse($response);
        } catch (\Throwable $e) {
            Log::warning('Specificity gate Claude call failed', ['idea_id' => $idea->id, 'error' => $e->getMessage()]);

            return $this->stubbedGateResult();
        }
    }

    /**
     * Score an idea across all 6 dimensions.
     *
     * @param  array{results: array<int, mixed>, summary: string, stubbed: bool}  $competitionData
     * @return array{scores: array<string, int>, overall: int, reasoning: array<string, string>, kill_condition: ?string, kill_reasoning: ?string}
     */
    public function scoreIdea(Idea $idea, array $competitionData): array
    {
        if (! $this->hasApiKey()) {
            return $this->stubbedScoreResult();
        }

        $successPatterns = SuccessPattern::all()->take(10);
        $prompt = $this->buildScoringPrompt($idea, $competitionData, $successPatterns);

        try {
            $response = $this->callClaude($prompt, maxTokens: 2000);

            return $this->parseScoringResponse($response);
        } catch (\Throwable $e) {
            Log::warning('Scoring Claude call failed', ['idea_id' => $idea->id, 'error' => $e->getMessage()]);

            return $this->stubbedScoreResult();
        }
    }

    private function hasApiKey(): bool
    {
        return (bool) config('scoring.anthropic.api_key');
    }

    private function callClaude(string $prompt, int $maxTokens): string
    {
        $response = Http::withHeaders([
            'x-api-key' => config('scoring.anthropic.api_key'),
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])
            ->timeout(config('scoring.anthropic.timeout', 60))
            ->post(config('scoring.anthropic.base_url') . '/messages', [
                'model' => config('scoring.anthropic.model', 'claude-sonnet-4-6'),
                'max_tokens' => $maxTokens,
                'system' => $this->systemPrompt(),
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException("Anthropic API error: {$response->status()} — {$response->body()}");
        }

        return $response->json('content.0.text', '');
    }

    private function systemPrompt(): string
    {
        $criteria = file_get_contents(base_path('docs/build/idea-scoring-criteria.md'));

        return <<<PROMPT
You are the scoring agent for the Lightbulb AI idea engine. Your job is to evaluate business ideas strictly against the scoring criteria below. Follow the criteria exactly — do not soften or inflate scores. Every cap, kill condition, and modifier described in the criteria must be applied.

Always respond with valid JSON only. No prose outside the JSON object.

{$criteria}
PROMPT;
    }

    private function buildGatePrompt(Idea $idea): string
    {
        return <<<PROMPT
Run the Specificity Gate (Section 6b) on this idea.

IDEA TITLE: {$idea->title}

SIGNALS SUMMARY:
{$idea->signals_summary}

Answer all four gate questions with one concrete sentence each. If any answer is vague or reveals a fatal flaw, set passed to false.

Respond with this JSON structure:
{
  "passed": true | false,
  "answers": {
    "day_one_action": "...",
    "free_tool_comparison": "...",
    "first_paying_customer": "...",
    "competitor_switch_reason": "..."
  },
  "reasoning": "One sentence explaining the gate decision."
}
PROMPT;
    }

    /** @param \Illuminate\Support\Collection<int, SuccessPattern> $patterns */
    private function buildScoringPrompt(Idea $idea, array $competitionData, \Illuminate\Support\Collection $patterns): string
    {
        $gateAnswers = json_encode($idea->specificity_gate_answers, JSON_PRETTY_PRINT);
        $patternList = $patterns->map(fn ($p) => "- {$p->product_name}: {$p->pain_solved} (${$p->mrr_amount}/mo, {$p->pricing_model})")->implode("\n");

        return <<<PROMPT
Score this idea across all 6 dimensions. Apply every cap, kill condition, and modifier from the criteria exactly.

IDEA TITLE: {$idea->title}

SPECIFICITY GATE ANSWERS:
{$gateAnswers}

SIGNALS SUMMARY:
{$idea->signals_summary}

COMPETITION SEARCH RESULTS:
{$competitionData['summary']}

SUCCESS PATTERN CORPUS (proven products that reached \$1K MRR):
{$patternList}

Check for hard kill conditions first. If any fire, set kill_condition to the matching condition name and score everything 0.

Respond with this JSON structure:
{
  "kill_condition": null | "requires_sales_calls" | "hardware_component" | "ad_supported_content",
  "kill_reasoning": null | "...",
  "scores": {
    "problem_strength": 0-100,
    "distribution_path": 0-100,
    "competition_gap": 0-100,
    "build_feasibility": 0-100,
    "automability": 0-100,
    "revenue_plausibility": 0-100
  },
  "overall": 0-100,
  "reasoning": {
    "problem_strength": "...",
    "distribution_path": "...",
    "competition_gap": "...",
    "build_feasibility": "...",
    "automability": "...",
    "revenue_plausibility": "...",
    "overall": "..."
  },
  "success_pattern_confidence": 0-100,
  "success_pattern_notes": "..."
}
PROMPT;
    }

    /** @return array{passed: bool, answers: array<string, string>, reasoning: string} */
    private function parseGateResponse(string $raw): array
    {
        $data = json_decode($this->extractJson($raw), true);

        if (! is_array($data)) {
            return $this->stubbedGateResult();
        }

        return [
            'passed' => (bool) ($data['passed'] ?? false),
            'answers' => $data['answers'] ?? [],
            'reasoning' => $data['reasoning'] ?? '',
        ];
    }

    /** @return array{scores: array<string, int>, overall: int, reasoning: array<string, string>, kill_condition: ?string, kill_reasoning: ?string} */
    private function parseScoringResponse(string $raw): array
    {
        $data = json_decode($this->extractJson($raw), true);

        if (! is_array($data)) {
            return $this->stubbedScoreResult();
        }

        return [
            'scores' => $data['scores'] ?? [],
            'overall' => (int) ($data['overall'] ?? 0),
            'reasoning' => $data['reasoning'] ?? [],
            'kill_condition' => $data['kill_condition'] ?? null,
            'kill_reasoning' => $data['kill_reasoning'] ?? null,
            'success_pattern_confidence' => (int) ($data['success_pattern_confidence'] ?? 0),
            'success_pattern_notes' => $data['success_pattern_notes'] ?? null,
        ];
    }

    private function extractJson(string $raw): string
    {
        // Strip markdown code fences if present
        if (preg_match('/```(?:json)?\s*([\s\S]+?)\s*```/', $raw, $matches)) {
            return $matches[1];
        }

        // Find first { to last }
        $start = strpos($raw, '{');
        $end = strrpos($raw, '}');

        if ($start !== false && $end !== false) {
            return substr($raw, $start, $end - $start + 1);
        }

        return $raw;
    }

    /** @return array{passed: bool, answers: array<string, string>, reasoning: string} */
    private function stubbedGateResult(): array
    {
        return [
            'passed' => true,
            'answers' => [
                'day_one_action' => '[STUB] User signs up and connects their data source.',
                'free_tool_comparison' => '[STUB] Free tools cannot automate this workflow at scale.',
                'first_paying_customer' => '[STUB] A solo SaaS founder with an existing customer base.',
                'competitor_switch_reason' => '[STUB] Existing tools are too expensive or missing key features.',
            ],
            'reasoning' => '[STUB] Gate auto-passed — ANTHROPIC_API_KEY not set.',
        ];
    }

    /** @return array{scores: array<string, int>, overall: int, reasoning: array<string, string>, kill_condition: ?string, kill_reasoning: ?string, success_pattern_confidence: int, success_pattern_notes: ?string} */
    private function stubbedScoreResult(): array
    {
        return [
            'scores' => [
                'problem_strength' => 60,
                'distribution_path' => 55,
                'competition_gap' => 60,
                'build_feasibility' => 65,
                'automability' => 70,
                'revenue_plausibility' => 55,
            ],
            'overall' => 61,
            'reasoning' => [
                'problem_strength' => '[STUB] Score placeholder — set ANTHROPIC_API_KEY for real scoring.',
                'distribution_path' => '[STUB] Score placeholder.',
                'competition_gap' => '[STUB] Score placeholder.',
                'build_feasibility' => '[STUB] Score placeholder.',
                'automability' => '[STUB] Score placeholder.',
                'revenue_plausibility' => '[STUB] Score placeholder.',
                'overall' => '[STUB] Weighted average placeholder.',
            ],
            'kill_condition' => null,
            'kill_reasoning' => null,
            'success_pattern_confidence' => 50,
            'success_pattern_notes' => '[STUB] Pattern matching not available without API key.',
        ];
    }
}

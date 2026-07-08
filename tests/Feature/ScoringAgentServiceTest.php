<?php

namespace Tests\Feature;

use App\Models\Idea;
use App\Services\Scoring\ClaudeCliRunner;
use App\Services\Scoring\ScoringAgentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ScoringAgentServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeService(?ClaudeCliRunner $runner = null): ScoringAgentService
    {
        return new ScoringAgentService($runner ?? new ClaudeCliRunner());
    }

    private function pendingIdea(): Idea
    {
        return Idea::factory()->create([
            'status' => 'pending',
            'specificity_gate_answers' => ['day_one_action' => 'test'],
            'competition_summary' => 'moderate competition',
        ]);
    }

    // ── hasDriver / stub fallback ─────────────────────────────────────────────

    public function test_returns_stub_gate_result_when_no_driver_configured(): void
    {
        Config::set('scoring.driver', 'api');
        Config::set('scoring.anthropic.api_key', null);

        $result = $this->makeService()->runSpecificityGate($this->pendingIdea());

        $this->assertTrue($result['passed']);
        $this->assertStringContainsString('[STUB]', $result['reasoning']);
    }

    public function test_returns_stub_score_result_when_no_driver_configured(): void
    {
        Config::set('scoring.driver', 'api');
        Config::set('scoring.anthropic.api_key', null);

        $result = $this->makeService()->scoreIdea($this->pendingIdea(), [
            'results' => [],
            'summary' => 'stub competition',
            'stubbed' => true,
        ]);

        $this->assertSame(61, $result['overall']);
        $this->assertStringContainsString('[STUB]', $result['reasoning']['problem_strength']);
    }

    // ── CLI driver ────────────────────────────────────────────────────────────

    public function test_cli_driver_calls_claude_cli_runner_for_gate(): void
    {
        Config::set('scoring.driver', 'cli');
        Config::set('scoring.claude_cli.path', 'claude');

        $gateJson = json_encode([
            'passed' => true,
            'answers' => [
                'day_one_action'          => 'Sign up and connect data.',
                'free_tool_comparison'    => 'Free tools lack automation.',
                'first_paying_customer'   => 'A solo SaaS founder.',
                'competitor_switch_reason' => 'Existing tools too expensive.',
            ],
            'reasoning' => 'Idea is specific enough.',
        ]);

        $runner = $this->createMock(ClaudeCliRunner::class);
        $runner->expects($this->once())
            ->method('run')
            ->willReturn($gateJson);

        $result = $this->makeService($runner)->runSpecificityGate($this->pendingIdea());

        $this->assertTrue($result['passed']);
        $this->assertSame('Idea is specific enough.', $result['reasoning']);
        $this->assertSame('Sign up and connect data.', $result['answers']['day_one_action']);
    }

    public function test_cli_driver_calls_claude_cli_runner_for_scoring(): void
    {
        Config::set('scoring.driver', 'cli');
        Config::set('scoring.claude_cli.path', 'claude');

        $scoreJson = json_encode([
            'kill_condition' => null,
            'kill_reasoning' => null,
            'scores' => [
                'problem_strength' => 80,
                'distribution_path' => 70,
                'competition_gap' => 75,
                'build_feasibility' => 85,
                'automability' => 90,
                'revenue_plausibility' => 72,
            ],
            'overall' => 78,
            'reasoning' => [
                'problem_strength' => 'Strong pain signal.',
                'distribution_path' => 'Clear path.',
                'competition_gap' => 'Gap exists.',
                'build_feasibility' => 'Feasible.',
                'automability' => 'Fully automatable.',
                'revenue_plausibility' => 'Plausible.',
                'overall' => 'Good candidate.',
            ],
            'success_pattern_confidence' => 65,
            'success_pattern_notes' => 'Similar to Bannerbear pattern.',
        ]);

        $runner = $this->createMock(ClaudeCliRunner::class);
        $runner->expects($this->once())
            ->method('run')
            ->willReturn($scoreJson);

        $result = $this->makeService($runner)->scoreIdea($this->pendingIdea(), [
            'results' => [],
            'summary' => 'Moderate competition.',
            'stubbed' => false,
        ]);

        $this->assertSame(78, $result['overall']);
        $this->assertSame(80, $result['scores']['problem_strength']);
        $this->assertSame(65, $result['success_pattern_confidence']);
        $this->assertNull($result['kill_condition']);
    }

    // ── API driver ────────────────────────────────────────────────────────────

    public function test_api_driver_calls_anthropic_api_for_gate(): void
    {
        Config::set('scoring.driver', 'api');
        Config::set('scoring.anthropic.api_key', 'test-api-key');
        Config::set('scoring.anthropic.base_url', 'https://api.anthropic.com/v1');

        $gateJson = json_encode([
            'passed' => false,
            'answers' => [],
            'reasoning' => 'Too vague.',
        ]);

        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => $gateJson]],
            ], 200),
        ]);

        $runner = $this->createMock(ClaudeCliRunner::class);
        $runner->expects($this->never())->method('run');

        $result = $this->makeService($runner)->runSpecificityGate($this->pendingIdea());

        $this->assertFalse($result['passed']);
        $this->assertSame('Too vague.', $result['reasoning']);
    }

    public function test_api_driver_falls_back_to_stub_on_http_error(): void
    {
        Config::set('scoring.driver', 'api');
        Config::set('scoring.anthropic.api_key', 'test-api-key');
        Config::set('scoring.anthropic.base_url', 'https://api.anthropic.com/v1');

        Http::fake(['*' => Http::response([], 500)]);

        $result = $this->makeService()->runSpecificityGate($this->pendingIdea());

        $this->assertTrue($result['passed']);
        $this->assertStringContainsString('[STUB]', $result['reasoning']);
    }

    // ── JSON extraction edge cases ────────────────────────────────────────────

    public function test_cli_driver_parses_json_wrapped_in_code_fence(): void
    {
        Config::set('scoring.driver', 'cli');
        Config::set('scoring.claude_cli.path', 'claude');

        $response = "Here is the gate result:\n```json\n" . json_encode([
            'passed' => true,
            'answers' => ['day_one_action' => 'test', 'free_tool_comparison' => 'test', 'first_paying_customer' => 'test', 'competitor_switch_reason' => 'test'],
            'reasoning' => 'Passed.',
        ]) . "\n```";

        $runner = $this->createStub(ClaudeCliRunner::class);
        $runner->method('run')->willReturn($response);

        $result = $this->makeService($runner)->runSpecificityGate($this->pendingIdea());

        $this->assertTrue($result['passed']);
    }

    public function test_cli_driver_falls_back_to_stub_when_runner_throws(): void
    {
        Config::set('scoring.driver', 'cli');
        Config::set('scoring.claude_cli.path', 'claude');

        $runner = $this->createStub(ClaudeCliRunner::class);
        $runner->method('run')->willThrowException(new \RuntimeException('claude not found'));

        $result = $this->makeService($runner)->runSpecificityGate($this->pendingIdea());

        $this->assertTrue($result['passed']);
        $this->assertStringContainsString('[STUB]', $result['reasoning']);
    }

    public function test_kill_condition_is_preserved_in_score_result(): void
    {
        Config::set('scoring.driver', 'cli');
        Config::set('scoring.claude_cli.path', 'claude');

        $scoreJson = json_encode([
            'kill_condition' => 'hardware_component',
            'kill_reasoning' => 'Requires physical hardware.',
            'scores' => [],
            'overall' => 0,
            'reasoning' => [],
            'success_pattern_confidence' => 0,
            'success_pattern_notes' => null,
        ]);

        $runner = $this->createStub(ClaudeCliRunner::class);
        $runner->method('run')->willReturn($scoreJson);

        $result = $this->makeService($runner)->scoreIdea($this->pendingIdea(), [
            'results' => [], 'summary' => '', 'stubbed' => false,
        ]);

        $this->assertSame('hardware_component', $result['kill_condition']);
        $this->assertSame('Requires physical hardware.', $result['kill_reasoning']);
    }
}

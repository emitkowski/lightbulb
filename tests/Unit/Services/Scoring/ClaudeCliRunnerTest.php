<?php

namespace Tests\Unit\Services\Scoring;

use App\Services\Scoring\ClaudeCliRunner;
use Tests\TestCase;

class ClaudeCliRunnerTest extends TestCase
{
    private ClaudeCliRunner $runner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->runner = new ClaudeCliRunner();
    }

    public function test_parse_stream_extracts_result_text(): void
    {
        $jsonl = implode("\n", [
            '{"type":"system","subtype":"init","cwd":"/app","session_id":"abc123"}',
            '{"type":"assistant","message":{"content":[{"type":"text","text":"Thinking..."}]}}',
            '{"type":"result","subtype":"success","result":"{\"passed\":true}","total_cost_usd":0.0012,"usage":{"input_tokens":100,"output_tokens":50}}',
        ]);

        $this->assertSame('{"passed":true}', $this->runner->parseStream($jsonl));
    }

    public function test_parse_stream_returns_empty_string_when_no_result_event(): void
    {
        $jsonl = implode("\n", [
            '{"type":"system","subtype":"init"}',
            '{"type":"assistant","message":{"content":[]}}',
        ]);

        $this->assertSame('', $this->runner->parseStream($jsonl));
    }

    public function test_parse_stream_ignores_malformed_lines(): void
    {
        $jsonl = implode("\n", [
            'not-json',
            '',
            '{"type":"result","result":"clean output"}',
        ]);

        $this->assertSame('clean output', $this->runner->parseStream($jsonl));
    }

    public function test_build_command_includes_required_flags(): void
    {
        $cmd = $this->runner->buildCommand('claude');

        $this->assertStringContainsString('--print', $cmd);
        $this->assertStringContainsString('--output-format stream-json', $cmd);
        $this->assertStringContainsString('--dangerously-skip-permissions', $cmd);
    }

    public function test_build_command_includes_model_when_provided(): void
    {
        $cmd = $this->runner->buildCommand('claude', 'claude-haiku-4-5');

        $this->assertStringContainsString('--model', $cmd);
        $this->assertStringContainsString('claude-haiku-4-5', $cmd);
    }

    public function test_build_command_omits_model_flag_when_null(): void
    {
        $cmd = $this->runner->buildCommand('claude', null);

        $this->assertStringNotContainsString('--model', $cmd);
    }

    public function test_build_command_escapes_path_with_spaces(): void
    {
        $cmd = $this->runner->buildCommand('/path with spaces/claude');

        $this->assertStringContainsString("'/path with spaces/claude'", $cmd);
    }
}

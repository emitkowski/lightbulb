<?php

namespace Tests\Unit\Services\Scoring;

use App\Services\Scoring\ClaudeCliRunner;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Tests ClaudeCliRunner::run() using real subprocesses with a fake claude shell script
 * so we exercise buildEnv(), createTempHome(), removeTempHome(), and parseStream() end-to-end.
 */
class ClaudeCliRunnerRunTest extends TestCase
{
    private string $fakeClaudePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeClaudePath = sys_get_temp_dir() . '/lightbulb-test-fake-claude-' . getmypid() . '.sh';

        file_put_contents($this->fakeClaudePath, implode("\n", [
            '#!/bin/bash',
            'echo \'{"type":"result","result":"hello from fake claude"}\'',
        ]));

        chmod($this->fakeClaudePath, 0755);

        Config::set('scoring.claude_cli.path', $this->fakeClaudePath);
        Config::set('scoring.claude_cli.model', null);
        Config::set('scoring.claude_cli.timeout', 10);
        Config::set('scoring.claude_cli.use_cli_auth', false);
        Config::set('scoring.anthropic.api_key', '');
    }

    protected function tearDown(): void
    {
        if (is_file($this->fakeClaudePath)) {
            unlink($this->fakeClaudePath);
        }

        parent::tearDown();
    }

    public function test_run_returns_result_text_from_subprocess(): void
    {
        $result = (new ClaudeCliRunner())->run('test prompt');

        $this->assertSame('hello from fake claude', $result);
    }

    public function test_run_creates_and_cleans_up_temp_home_when_cli_auth_enabled(): void
    {
        Config::set('scoring.claude_cli.use_cli_auth', true);
        Config::set('scoring.claude_cli.home', sys_get_temp_dir());

        $tempDirsBefore = glob(sys_get_temp_dir() . '/lightbulb-claude-home-*') ?: [];

        $result = (new ClaudeCliRunner())->run('test prompt');

        $tempDirsAfter = glob(sys_get_temp_dir() . '/lightbulb-claude-home-*') ?: [];

        $this->assertSame('hello from fake claude', $result);
        $this->assertCount(count($tempDirsBefore), $tempDirsAfter, 'Temp HOME was not cleaned up after run');
    }

    public function test_run_returns_empty_string_when_subprocess_produces_no_result_event(): void
    {
        $noResultScript = sys_get_temp_dir() . '/lightbulb-test-no-result-' . getmypid() . '.sh';
        file_put_contents($noResultScript, "#!/bin/bash\necho '{\"type\":\"text\",\"text\":\"some text\"}'\n");
        chmod($noResultScript, 0755);

        try {
            Config::set('scoring.claude_cli.path', $noResultScript);
            $result = (new ClaudeCliRunner())->run('test prompt');
            $this->assertSame('', $result);
        } finally {
            unlink($noResultScript);
        }
    }

    public function test_run_continues_after_non_zero_exit_and_returns_parsed_output(): void
    {
        $exitOneScript = sys_get_temp_dir() . '/lightbulb-test-exit-one-' . getmypid() . '.sh';
        file_put_contents($exitOneScript, implode("\n", [
            '#!/bin/bash',
            'echo \'{"type":"result","result":"partial output"}\'',
            'exit 1',
        ]));
        chmod($exitOneScript, 0755);

        try {
            Config::set('scoring.claude_cli.path', $exitOneScript);
            $result = (new ClaudeCliRunner())->run('test prompt');
            $this->assertSame('partial output', $result);
        } finally {
            unlink($exitOneScript);
        }
    }
}

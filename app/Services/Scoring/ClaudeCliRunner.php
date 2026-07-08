<?php

namespace App\Services\Scoring;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class ClaudeCliRunner
{
    /**
     * Run a prompt through the Claude CLI subprocess and return the text response.
     *
     * Prompt is fed via stdin. Output goes to a temp file to avoid pipe-buffer issues
     * with Node.js. The JSONL stream-json format is parsed to extract the result event.
     *
     * CLAUDECODE and CLAUDE_CODE_PATH are explicitly cleared so the subprocess does
     * not treat itself as a nested Claude Code session and hang trying to connect back.
     */
    public function run(string $prompt): string
    {
        $claudePath = config('scoring.claude_cli.path', 'claude');
        $timeout    = (int) config('scoring.claude_cli.timeout', 120);
        $model      = config('scoring.claude_cli.model', 'claude-sonnet-4-6') ?: null;

        $outputFile = sys_get_temp_dir() . '/lightbulb-claude-' . uniqid() . '.jsonl';

        [$env, $tempHome] = $this->buildEnv();

        $command = $this->buildCommand($claudePath, $model) . ' >' . escapeshellarg($outputFile);

        $process = Process::fromShellCommandline(
            $command,
            cwd: base_path(),
            env: $env,
            timeout: $timeout,
        )->setInput($prompt);

        Log::info('[claude-cli] starting scoring run', ['output' => $outputFile]);

        $startTime = microtime(true);

        try {
            $process->run();
        } finally {
            $rawOutput = is_file($outputFile) ? (file_get_contents($outputFile) ?: '') : '';
            @unlink($outputFile);
            if ($tempHome !== null) {
                $this->removeTempHome($tempHome);
            }
        }

        $durationMs = (int) ((microtime(true) - $startTime) * 1000);

        if (! $process->isSuccessful()) {
            Log::warning('[claude-cli] non-zero exit', [
                'exit_code' => $process->getExitCode(),
                'stderr'    => $process->getErrorOutput(),
            ]);
        }

        $text = $this->parseStream($rawOutput);

        Log::info('[claude-cli] done', ['duration_ms' => $durationMs, 'chars' => strlen($text)]);

        return $text;
    }

    public function buildCommand(string $claudePath, ?string $model = null): string
    {
        $cmd = escapeshellarg($claudePath) . ' --print --output-format stream-json --verbose --dangerously-skip-permissions';

        if ($model !== null && $model !== '') {
            $cmd .= ' --model ' . escapeshellarg($model);
        }

        return $cmd;
    }

    /**
     * Parse stream-json JSONL output and extract the text from the result event.
     */
    public function parseStream(string $rawOutput): string
    {
        foreach (explode("\n", $rawOutput) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $event = json_decode($line, true);
            if (! is_array($event) || ($event['type'] ?? '') !== 'result') {
                continue;
            }

            return $event['result'] ?? '';
        }

        return '';
    }

    /**
     * @return array{0: array<string, string>, 1: string|null}
     */
    private function buildEnv(): array
    {
        $env = [
            'PATH'             => getenv('PATH') ?: '',
            'NO_COLOR'         => '1',
            'TERM'             => 'dumb',
            // Prevent the subprocess from treating itself as a nested Claude Code session.
            'CLAUDECODE'       => '',
            'CLAUDE_CODE_PATH' => '',
        ];

        $tempHome = null;

        if (config('scoring.claude_cli.use_cli_auth', true)) {
            $claudeHome = config('scoring.claude_cli.home', getenv('HOME') ?: '/root');
            $tempHome   = $this->createTempHome($claudeHome);
            $env['HOME']             = $tempHome;
            $env['ANTHROPIC_API_KEY'] = '';
        } else {
            $env['HOME']             = getenv('HOME') ?: '/root';
            $env['ANTHROPIC_API_KEY'] = config('scoring.anthropic.api_key', '');
        }

        return [$env, $tempHome];
    }

    /**
     * Create a per-run writable HOME with only the OAuth credentials symlinked in.
     * Needed because the real ~/.claude may be read-only or mounted in Docker.
     */
    private function createTempHome(string $claudeHome): string
    {
        $tempHome  = sys_get_temp_dir() . '/lightbulb-claude-home-' . uniqid();
        $dotClaude = $tempHome . '/.claude';

        mkdir($dotClaude, 0700, true);

        $credentials = $claudeHome . '/.claude/.credentials.json';
        if (file_exists($credentials)) {
            symlink($credentials, $dotClaude . '/.credentials.json');
        }

        return $tempHome;
    }

    private function removeTempHome(string $tempHome): void
    {
        $credentials = $tempHome . '/.claude/.credentials.json';
        if (is_link($credentials)) {
            unlink($credentials);
        }
        $this->removeDirectory($tempHome);
    }

    private function removeDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        foreach (scandir($path) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $full = $path . '/' . $item;
            is_dir($full) ? $this->removeDirectory($full) : @unlink($full);
        }

        @rmdir($path);
    }
}

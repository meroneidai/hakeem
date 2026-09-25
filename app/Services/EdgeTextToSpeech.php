<?php

namespace App\Services;

use Illuminate\Process\Exceptions\ProcessFailedException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Throwable;

class EdgeTextToSpeech
{
    /**
     * Synthesize speech and return an absolute path to a cached audio file, or null on failure.
     */
    public function synthesize(string $text): ?string
    {
        $text = trim(Str::limit($text, 600, ''));

        if ($text === '') {
            return null;
        }

        $voice = (string) config('agent.tts_voice', 'ar-EG-ShakirNeural');
        $hash = hash('sha256', $voice.'|'.$text);
        $directory = storage_path('app/private/tts');
        $path = $directory.'/'.$hash.'.mp3';

        if (is_file($path) && filemtime($path) > now()->subDays(7)->getTimestamp()) {
            return $path;
        }

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            return null;
        }

        $script = (string) config('agent.tts_script');
        $python = (string) config('agent.tts_python', 'python3');

        if ($script === '' || ! is_file($script)) {
            Log::warning('hakeem.tts.missing_script', ['script' => $script]);

            return null;
        }

        $temp = $directory.'/'.$hash.'.tmp.mp3';

        try {
            $result = Process::timeout(30)
                ->env([
                    'EDGE_TTS_VOICE' => $voice,
                    'PATH' => getenv('PATH') ?: '/usr/bin:/bin',
                ])
                ->run([$python, $script, $text, $temp]);

            if ($result->failed() || ! is_file($temp) || filesize($temp) < 64) {
                Log::warning('hakeem.tts.failed', [
                    'exit' => $result->exitCode(),
                    'error' => Str::limit($result->errorOutput(), 400),
                ]);

                @unlink($temp);

                return null;
            }

            @rename($temp, $path);

            return is_file($path) ? $path : null;
        } catch (ProcessFailedException|Throwable $exception) {
            report($exception);
            @unlink($temp);

            return null;
        }
    }
}

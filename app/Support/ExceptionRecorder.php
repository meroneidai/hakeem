<?php

namespace App\Support;

use App\Models\ExceptionReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ExceptionRecorder
{
    public function record(Throwable $exception, ?Request $request = null): void
    {
        if ($this->shouldIgnore($exception)) {
            return;
        }

        try {
            if (! Schema::hasTable('exception_reports')) {
                return;
            }

            $request ??= request();
            $file = $exception->getFile();
            $line = $exception->getLine();
            $class = $exception::class;

            $existing = ExceptionReport::query()
                ->whereNull('resolved_at')
                ->where('exception_class', $class)
                ->where('file', $file)
                ->where('line', $line)
                ->first();

            if ($existing) {
                $existing->forceFill([
                    'message' => mb_substr($exception->getMessage(), 0, 2000),
                    'occurrences' => $existing->occurrences + 1,
                    'last_seen_at' => now(),
                    'user_id' => Auth::id() ?? $existing->user_id,
                    'url' => $request?->fullUrl(),
                    'method' => $request?->method(),
                ])->save();

                return;
            }

            ExceptionReport::query()->create([
                'user_id' => Auth::id(),
                'exception_class' => $class,
                'message' => mb_substr($exception->getMessage() ?: $class, 0, 2000),
                'file' => $file,
                'line' => $line,
                'url' => $request?->fullUrl(),
                'method' => $request?->method(),
                'status' => $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500,
                'trace' => mb_substr($exception->getTraceAsString(), 0, 8000),
                'occurrences' => 1,
                'last_seen_at' => now(),
            ]);
        } catch (Throwable) {
            // Recording must never replace the original exception.
        }
    }

    private function shouldIgnore(Throwable $exception): bool
    {
        if ($exception instanceof ValidationException) {
            return true;
        }

        if ($exception instanceof HttpExceptionInterface && $exception->getStatusCode() < 500) {
            return true;
        }

        return false;
    }
}

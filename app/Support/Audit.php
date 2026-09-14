<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Admin action trail. Required for compliance (md_files/07 §7) — every write in
 * the admin dashboard records who did what, to which record, and from where.
 */
class Audit
{
    public static function log(string $action, ?Model $subject = null, array $changes = []): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => $subject ? $subject::class : null,
            'auditable_id' => $subject?->getKey(),
            'changes' => $changes ?: null,
            'ip_address' => Request::ip(),
            'user_agent' => substr((string) Request::userAgent(), 0, 255),
            'created_at' => now(),
        ]);
    }

    public static function created(Model $subject): void
    {
        static::log(static::action($subject, 'created'), $subject, ['after' => static::snapshot($subject)]);
    }

    public static function updated(Model $subject, array $before = []): void
    {
        $changed = $subject->getChanges();
        unset($changed['updated_at']);

        static::log(static::action($subject, 'updated'), $subject, [
            'before' => array_intersect_key($before, $changed),
            'after' => static::redact($changed),
        ]);
    }

    public static function deleted(Model $subject): void
    {
        static::log(static::action($subject, 'deleted'), $subject, ['before' => static::snapshot($subject)]);
    }

    private static function action(Model $subject, string $verb): string
    {
        return str(class_basename($subject))->snake()->toString().'.'.$verb;
    }

    private static function snapshot(Model $subject): array
    {
        return static::redact($subject->attributesToArray());
    }

    private static function redact(array $attributes): array
    {
        foreach (['password', 'remember_token', 'value'] as $sensitive) {
            if (array_key_exists($sensitive, $attributes)) {
                $attributes[$sensitive] = '[redacted]';
            }
        }

        return $attributes;
    }
}

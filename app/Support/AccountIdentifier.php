<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * First character decides the channel: digit or + is an Egyptian phone,
 * anything else is treated as an email address.
 */
class AccountIdentifier
{
    public function __construct(
        public readonly string $channel,
        public readonly ?string $phone,
        public readonly ?string $email,
        public readonly string $raw,
    ) {}

    public static function fromRequest(mixed $identifier = null, mixed $phone = null, mixed $email = null): self
    {
        $raw = trim((string) ($identifier ?: $phone ?: $email ?: ''));

        return self::from($raw);
    }

    public static function from(string $raw): self
    {
        $raw = trim($raw);

        if ($raw === '') {
            throw ValidationException::withMessages([
                'identifier' => __('auth.identifier_required'),
            ]);
        }

        if (self::startsAsPhone($raw)) {
            $phone = User::normalizePhone($raw);

            if (strlen($phone) < 11) {
                throw ValidationException::withMessages([
                    'identifier' => __('auth.invalid_phone'),
                ]);
            }

            return new self('phone', $phone, null, $raw);
        }

        $email = Str::lower($raw);

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'identifier' => __('auth.invalid_email'),
            ]);
        }

        return new self('email', null, $email, $raw);
    }

    public function findUser(): ?User
    {
        if ($this->channel === 'phone') {
            return User::query()->where('phone', $this->phone)->first();
        }

        return User::query()->whereRaw('LOWER(email) = ?', [$this->email])->first();
    }

    public function assertAvailable(?int $ignoreId = null): void
    {
        $query = $this->channel === 'phone'
            ? User::query()->where('phone', $this->phone)
            : User::query()->whereRaw('LOWER(email) = ?', [$this->email]);

        if ($ignoreId) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'identifier' => __('auth.identifier_taken'),
            ]);
        }
    }

    public function throttleKey(string $prefix, string $ip): string
    {
        $id = $this->phone ?? $this->email ?? $this->raw;

        return $prefix.':'.strtolower((string) $id).'|'.$ip;
    }

    public static function startsAsPhone(string $raw): bool
    {
        $first = mb_substr(ltrim($raw), 0, 1);

        if ($first === '+' || ($first !== '' && preg_match('/^[0-9٠-٩۰-۹]$/u', $first) === 1)) {
            return true;
        }

        return false;
    }
}

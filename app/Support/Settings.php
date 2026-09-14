<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * Thin read/write layer over the `settings` table, cached as a single payload
 * so admin-configured platform behaviour costs at most one query per request.
 */
class Settings
{
    private const CACHE_KEY = 'hakeem.settings';

    private ?array $loaded = null;

    public function all(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        return $this->loaded = Cache::rememberForever(self::CACHE_KEY, function () {
            return Setting::all()
                ->mapWithKeys(fn (Setting $setting) => [
                    $setting->key => [
                        'value' => $setting->value,
                        'group' => $setting->group,
                        'is_encrypted' => $setting->is_encrypted,
                    ],
                ])
                ->all();
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $entry = $this->all()[$key] ?? null;

        if ($entry === null) {
            return $default;
        }

        if ($entry['is_encrypted']) {
            try {
                return Crypt::decryptString((string) $entry['value']);
            } catch (\Throwable) {
                return $default;
            }
        }

        return $entry['value'] ?? $default;
    }

    public function bool(string $key, bool $default = false): bool
    {
        return (bool) $this->get($key, $default);
    }

    /**
     * @return array<string, mixed>
     */
    public function group(string $group): array
    {
        $values = [];

        foreach ($this->all() as $key => $entry) {
            if ($entry['group'] === $group) {
                $values[$key] = $this->get($key);
            }
        }

        return $values;
    }

    public function set(string $key, mixed $value, string $group = 'general', bool $encrypted = false): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            [
                'group' => $group,
                'value' => $encrypted && filled($value) ? Crypt::encryptString((string) $value) : $value,
                'is_encrypted' => $encrypted && filled($value),
            ],
        );

        $this->flush();
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function setMany(array $values, string $group = 'general', array $encryptedKeys = []): void
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $group, in_array($key, $encryptedKeys, true));
        }
    }

    public function flush(): void
    {
        $this->loaded = null;
        Cache::forget(self::CACHE_KEY);
    }
}

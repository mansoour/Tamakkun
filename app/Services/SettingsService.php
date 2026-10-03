<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Contracts\Cache\Repository as Cache;
use InvalidArgumentException;

/**
 * Database-backed platform settings with a cached read path.
 *
 * Values are stored JSON-encoded so integers, booleans and null survive a
 * round trip. Writing a setting clears the cache and writes an audit log.
 */
class SettingsService
{
    public const CACHE_KEY = 'settings.all';

    public function __construct(
        private readonly Cache $cache,
        private readonly AuditLogger $audit,
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $stored = $this->stored();

        if (array_key_exists($key, $stored)) {
            return $stored[$key];
        }

        return $this->defaults()[$key] ?? $default;
    }

    /**
     * All known settings, with stored values overriding the defaults.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return array_merge($this->defaults(), array_intersect_key($this->stored(), $this->defaults()));
    }

    public function set(string $key, mixed $value): void
    {
        $this->setMany([$key => $value]);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function setMany(array $values): void
    {
        $unknown = array_diff_key($values, $this->defaults());

        if ($unknown !== []) {
            throw new InvalidArgumentException('Unknown setting(s): '.implode(', ', array_keys($unknown)));
        }

        $old = array_intersect_key($this->all(), $values);

        foreach ($values as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => json_encode($value, JSON_UNESCAPED_UNICODE)]);
        }

        $this->flush();

        $this->audit->record('settings.updated', null, $old, $values);
    }

    public function flush(): void
    {
        $this->cache->forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, mixed>
     */
    private function stored(): array
    {
        return $this->cache->rememberForever(self::CACHE_KEY, fn (): array => Setting::query()
            ->pluck('value', 'key')
            ->map(fn (?string $value): mixed => $value === null ? null : json_decode($value, true))
            ->all());
    }

    /**
     * @return array<string, mixed>
     */
    private function defaults(): array
    {
        return config('tamakkun.settings', []);
    }
}

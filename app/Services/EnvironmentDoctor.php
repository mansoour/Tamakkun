<?php

namespace App\Services;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Checks that a machine can run Tamakkun (`php artisan tamakkun:doctor`).
 *
 * Useful on a fresh developer PC (Windows + XAMPP MariaDB) and on the
 * server after an update. Read-only: it never changes anything.
 */
class EnvironmentDoctor
{
    public const MIN_PHP = '8.3.0';

    public const EXTENSIONS = [
        'bcmath', 'ctype', 'curl', 'fileinfo', 'gd', 'intl', 'mbstring',
        'openssl', 'pdo_mysql', 'pdo_sqlite', 'tokenizer', 'xml', 'zip',
    ];

    public function __construct(private readonly Migrator $migrator) {}

    /**
     * @return list<array{check: string, status: 'ok'|'warn'|'fail', detail: string}>
     */
    public function run(): array
    {
        return [
            $this->php(),
            $this->extensions(),
            $this->appKey(),
            $this->timezone(),
            $this->debugMode(),
            ...$this->database(),
            $this->writable(storage_path(), 'storage writable'),
            $this->writable(base_path('bootstrap/cache'), 'bootstrap/cache writable'),
            $this->storageLink(),
            $this->assets(),
            $this->mail(),
            $this->queue(),
            $this->pdfFonts(),
        ];
    }

    /**
     * @return array{check: string, status: 'ok'|'warn'|'fail', detail: string}
     */
    private function result(string $check, string $status, string $detail): array
    {
        return ['check' => $check, 'status' => $status, 'detail' => $detail];
    }

    private function php(): array
    {
        $ok = version_compare(PHP_VERSION, self::MIN_PHP, '>=');

        return $this->result('PHP version', $ok ? 'ok' : 'fail', PHP_VERSION.' ('.PHP_BINARY.')'.($ok ? '' : ' — PHP 8.3 is required'));
    }

    private function extensions(): array
    {
        $missing = array_values(array_filter(self::EXTENSIONS, fn (string $ext) => ! extension_loaded($ext)));

        return $missing === []
            ? $this->result('PHP extensions', 'ok', 'all '.count(self::EXTENSIONS).' loaded')
            : $this->result('PHP extensions', 'fail', 'missing: '.implode(', ', $missing).' — enable them in php.ini');
    }

    private function appKey(): array
    {
        return config('app.key')
            ? $this->result('APP_KEY', 'ok', 'set')
            : $this->result('APP_KEY', 'fail', 'missing — run php artisan key:generate');
    }

    private function timezone(): array
    {
        $timezone = config('app.timezone');

        return $this->result('Timezone', $timezone === 'Asia/Riyadh' ? 'ok' : 'warn', $timezone.($timezone === 'Asia/Riyadh' ? '' : ' — expected Asia/Riyadh'));
    }

    private function debugMode(): array
    {
        $env = app()->environment();
        $debug = (bool) config('app.debug');

        if ($env === 'production' && $debug) {
            return $this->result('Environment', 'fail', 'APP_DEBUG=true in production');
        }

        return $this->result('Environment', 'ok', "{$env}, debug ".($debug ? 'on' : 'off'));
    }

    /**
     * @return list<array{check: string, status: 'ok'|'warn'|'fail', detail: string}>
     */
    private function database(): array
    {
        try {
            DB::connection()->getPdo();
        } catch (Throwable $e) {
            return [$this->result('Database connection', 'fail', class_basename($e).': '.str($e->getMessage())->limit(140))];
        }

        $connection = DB::connection()->getDriverName().' '.DB::connection()->getDatabaseName();

        if (! $this->migrator->repositoryExists()) {
            return [
                $this->result('Database connection', 'ok', $connection),
                $this->result('Migrations', 'fail', 'not installed — run php artisan migrate'),
            ];
        }

        $files = $this->migrator->getMigrationFiles($this->migrator->paths() ?: [database_path('migrations')]);
        $pending = array_diff(array_keys($files), $this->migrator->getRepository()->getRan());

        return [
            $this->result('Database connection', 'ok', $connection),
            $pending === []
                ? $this->result('Migrations', 'ok', 'up to date')
                : $this->result('Migrations', 'fail', count($pending).' pending — run php artisan migrate'),
        ];
    }

    private function writable(string $path, string $check): array
    {
        return is_writable($path)
            ? $this->result($check, 'ok', $path)
            : $this->result($check, 'fail', "{$path} is not writable");
    }

    private function storageLink(): array
    {
        return file_exists(public_path('storage'))
            ? $this->result('Storage link', 'ok', 'public/storage exists')
            : $this->result('Storage link', 'warn', 'missing — run php artisan storage:link (needed for uploaded images)');
    }

    private function assets(): array
    {
        if (file_exists(public_path('hot'))) {
            return $this->result('Frontend assets', 'ok', 'Vite dev server running');
        }

        return file_exists(public_path('build/manifest.json'))
            ? $this->result('Frontend assets', 'ok', 'built (public/build)')
            : $this->result('Frontend assets', 'fail', 'not built — run npm run build (or npm run dev)');
    }

    private function mail(): array
    {
        $mailer = (string) config('mail.default');

        if (app()->environment('production') && in_array($mailer, ['log', 'array'], true)) {
            return $this->result('Mail', 'warn', "MAIL_MAILER={$mailer}: emails are not sent in production");
        }

        return $this->result('Mail', 'ok', "MAIL_MAILER={$mailer}");
    }

    private function queue(): array
    {
        $connection = (string) config('queue.default');

        return $connection === 'sync'
            ? $this->result('Queue', 'warn', 'QUEUE_CONNECTION=sync: emails are sent during the request')
            : $this->result('Queue', 'ok', "QUEUE_CONNECTION={$connection} (run php artisan queue:work)");
    }

    private function pdfFonts(): array
    {
        $missing = array_filter(config('reports.pdf.font_files'), fn (string $file) => ! is_file(config('reports.pdf.font_dir').DIRECTORY_SEPARATOR.$file));

        return $missing === []
            ? $this->result('PDF fonts', 'ok', 'IBM Plex Sans Arabic bundled')
            : $this->result('PDF fonts', 'fail', 'missing: '.implode(', ', $missing));
    }
}

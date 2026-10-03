<?php

namespace Tests\Feature;

use App\Services\EnvironmentDoctor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnvironmentDoctorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{check: string, status: string, detail: string}>
     */
    private function results(): array
    {
        return collect(app(EnvironmentDoctor::class)->run())->keyBy('check')->all();
    }

    public function test_it_reports_php_extensions_database_and_migrations(): void
    {
        $results = $this->results();

        $this->assertSame('ok', $results['PHP version']['status']);
        $this->assertSame('ok', $results['PHP extensions']['status']);
        $this->assertSame('ok', $results['Database connection']['status']);
        $this->assertSame('ok', $results['Migrations']['status']);
        $this->assertSame('ok', $results['PDF fonts']['status']);
    }

    public function test_problems_are_reported_and_the_command_fails(): void
    {
        config(['app.key' => '', 'app.env' => 'production', 'app.debug' => true]);
        $this->app['env'] = 'production';

        $results = $this->results();
        $this->assertSame('fail', $results['APP_KEY']['status']);
        $this->assertSame('fail', $results['Environment']['status']);

        $this->artisan('tamakkun:doctor')->expectsOutputToContain('problem(s) to fix')->assertExitCode(1);
    }
}

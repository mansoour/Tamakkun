<?php

namespace App\Jobs;

use App\Models\StudentImport;
use App\Services\StudentImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Creates the accounts of a confirmed CSV import. Hashing hundreds of
 * passwords is too slow for a web request, so it runs on the queue.
 */
class ImportStudents implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 900;

    public function __construct(public StudentImport $import) {}

    public function handle(StudentImportService $service): void
    {
        $service->run($this->import);
    }
}

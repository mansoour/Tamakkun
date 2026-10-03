<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\PdfRenderer;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Reports for counselors (/counselor/reports, assigned students) and admins
 * (/admin/reports, all students). HTML, CSV (UTF-8 with BOM for Excel)
 * and Arabic PDF share one data source: ReportService.
 */
class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function index(Request $request): View
    {
        return view('shared.reports.index', ['area' => $this->area($request), 'reports' => ReportService::REPORTS]);
    }

    public function show(Request $request, string $report, AuditLogger $audit, PdfRenderer $pdf): View|Response|StreamedResponse
    {
        abort_unless(isset(ReportService::REPORTS[$report]), 404);

        $data = $this->reports->build($report, $request->user());
        $format = $request->query('format');

        if (in_array($format, ['csv', 'pdf'], true)) {
            $audit->record('report.exported', null, null, ['report' => $report, 'format' => $format, 'rows' => count($data['rows'])]);
        }

        $filename = "tamakkun-{$report}-".now()->format('Y-m-d');

        return match ($format) {
            'csv' => response()->streamDownload(function () use ($data) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                fputcsv($out, $data['columns'], ',', '"', '');
                foreach ($data['rows'] as $row) {
                    fputcsv($out, array_map(fn ($v) => self::csvSafe($v), $row), ',', '"', '');
                }
                fclose($out);
            }, "{$filename}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']),
            'pdf' => response($pdf->render(view('shared.reports.pdf', ['report' => $data])->render(), $data['title']), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"{$filename}.pdf\"",
            ]),
            default => view('shared.reports.show', ['area' => $this->area($request), 'report' => $data]),
        };
    }

    /**
     * Neutralises spreadsheet formula injection (cells starting with = + - @
     * tab or CR) while keeping signed numbers such as "+11" intact.
     */
    public static function csvSafe(string|int|float|null $value): string|int|float
    {
        if ($value === null) {
            return '';
        }

        if (is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) === 1 && preg_match('/^[+-]?\d+(\.\d+)?$/', $value) !== 1) {
            return "'".$value;
        }

        return $value;
    }

    private function area(Request $request): string
    {
        return str_starts_with((string) $request->route()->getName(), 'admin.') ? 'admin' : 'counselor';
    }
}

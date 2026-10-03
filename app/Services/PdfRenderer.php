<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

/**
 * Arabic RTL PDF rendering with mPDF and the bundled IBM Plex Sans Arabic
 * font (config/reports.php). Never loads remote fonts.
 */
class PdfRenderer
{
    public function render(string $html, string $title): string
    {
        $config = config('reports.pdf');
        File::ensureDirectoryExists($config['temp_dir']);

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'tempDir' => $config['temp_dir'],
            'fontDir' => array_merge((new ConfigVariables)->getDefaults()['fontDir'], [$config['font_dir']]),
            'fontdata' => (new FontVariables)->getDefaults()['fontdata'] + [
                $config['font'] => $config['font_files'] + ['useOTL' => 0xFF, 'useKashida' => 75],
            ],
            'default_font' => $config['font'],
            'directionality' => 'rtl',
            'margin_top' => 14,
            'margin_bottom' => 14,
        ]);

        $mpdf->SetTitle($title);
        $mpdf->SetCreator('Tamakkun');
        $mpdf->SetDirectionality('rtl');
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    }
}

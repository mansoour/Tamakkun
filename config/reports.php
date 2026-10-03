<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PDF reports (mPDF)
    |--------------------------------------------------------------------------
    |
    | Fonts are bundled in resources/fonts (IBM Plex Sans Arabic, SIL OFL 1.1)
    | so PDF generation never depends on a remote font service.
    |
    */

    'pdf' => [
        'font_dir' => resource_path('fonts'),
        'temp_dir' => storage_path('app/mpdf-temp'),
        'font' => 'plexarabic',
        'font_files' => [
            'R' => 'IBMPlexSansArabic-Regular.ttf',
            'B' => 'IBMPlexSansArabic-Bold.ttf',
        ],
    ],

];

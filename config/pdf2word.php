<?php

return [

    /*
    |--------------------------------------------------------------------------
    | LibreOffice Binary Path
    |--------------------------------------------------------------------------
    |
    | Path to the LibreOffice soffice binary used for PDF/Office conversions.
    | On Windows use forward slashes: C:/Program Files/LibreOffice/program/soffice.exe
    | On Linux/Mac: /usr/bin/soffice
    |
    */
    'libreoffice_path' => env('LIBREOFFICE_PATH', 'soffice'),

    /*
    |--------------------------------------------------------------------------
    | Max Upload Size (MB)
    |--------------------------------------------------------------------------
    */
    'max_upload_size_mb' => (int) env('MAX_UPLOAD_SIZE_MB', 200),

    /*
    |--------------------------------------------------------------------------
    | File Retention (hours per plan)
    |--------------------------------------------------------------------------
    */
    'retention' => [
        'free' => (int) env('FILE_RETENTION_FREE_HOURS', 2),
        'pro' => (int) env('FILE_RETENTION_PRO_HOURS', 168),
        'business' => (int) env('FILE_RETENTION_BUSINESS_HOURS', 720),
    ],

    /*
    |--------------------------------------------------------------------------
    | Ghostscript Binary
    |--------------------------------------------------------------------------
    */
    'ghostscript_path' => env('GHOSTSCRIPT_PATH', 'gs'),

    /*
    |--------------------------------------------------------------------------
    | Temp Directory for Processing
    |--------------------------------------------------------------------------
    */
    'tmp_dir' => env('PDF_TMP_DIR', storage_path('app/tmp')),

    /*
    |--------------------------------------------------------------------------
    | Output File Expiry (minutes for signed download URLs)
    |--------------------------------------------------------------------------
    */
    'download_url_ttl_minutes' => (int) env('DOWNLOAD_URL_TTL_MINUTES', 60),

];

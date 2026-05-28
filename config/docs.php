<?php

return [
    /**
     * Default disk used for new uploads. Each row in uploaded_documents stores
     * its own `disk` so changing this does NOT affect existing files.
     */
    'default_disk' => env('DOCS_DEFAULT_DISK', 'documents_local'),

    'allowed_disks' => ['documents_local', 'documents_s3'],

    'fallback_mimes' => ['application/pdf', 'image/jpeg', 'image/png'],

    'fallback_max_size_kb' => 2048,

    'signed_url_minutes' => 5,
];

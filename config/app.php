<?php

/**
 * Application configuration.
 * Adjust timezone and salt if needed; database credentials live in database.php.
 */

return [
    'name' => 'Purchase Entry & Reporting',
    'timezone' => 'Asia/Dhaka',
    // Server-side salt used when generating hash_key (SHA-512 of receipt_id + salt)
    'hash_salt' => 'purchase_entry_salt_2024_secure_key',
    // Cookie that blocks repeat submissions for 24 hours
    'submit_cookie_name' => 'purchase_submitted',
    'submit_cookie_ttl' => 86400, // 24 hours in seconds

    /**
     * Web base path — leave EMPTY for automatic detection (recommended).
     *
     * Auto examples:
     *   https://example.com/              → ""
     *   https://example.com/purchase-entry-track/ → "/purchase-entry-track"
     *   http://localhost:8888/purchase-entry-track/ → "/purchase-entry-track"
     *
     * Only set manually if auto-detect fails, e.g. 'base_url' => '/purchase-entry-track'
     */
    'base_url' => '',

    /**
     * false = /index.php/report (works on LiteSpeed without rewrite).
     * true  = /report (needs .htaccess rewrite).
     * Do not add report/store folders.
     */
    'pretty_urls' => false,
];

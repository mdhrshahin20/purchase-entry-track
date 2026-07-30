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
    'base_url' => '', // Leave empty for auto-detection under /purchase-entry-track/public
];

<?php

declare(strict_types=1);

return [
    'client_id' => getenv('GOOGLE_CLIENT_ID') ?: 'YOUR_GOOGLE_CLIENT_ID',
    'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: 'YOUR_GOOGLE_CLIENT_SECRET',
    'redirect_uri' => getenv('GOOGLE_REDIRECT_URI') ?: 'http://localhost/auth/google_callback.php',
];

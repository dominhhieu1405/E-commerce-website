<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$config = require __DIR__ . '/../config/google_oauth.php';

if ($config['client_id'] === 'YOUR_GOOGLE_CLIENT_ID' || $config['client_secret'] === 'YOUR_GOOGLE_CLIENT_SECRET') {
    http_response_code(500);
    exit('Google OAuth chưa được cấu hình.');
}

$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

$params = http_build_query([
    'client_id' => $config['client_id'],
    'redirect_uri' => $config['redirect_uri'],
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'access_type' => 'online',
    'state' => $state,
    'prompt' => 'select_account',
]);

header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
exit;

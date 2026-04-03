<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

$config = require __DIR__ . '/../config/google_oauth.php';

$state = (string) ($_GET['state'] ?? '');
$code = (string) ($_GET['code'] ?? '');

if ($state === '' || $code === '' || !isset($_SESSION['oauth_state']) || !hash_equals($_SESSION['oauth_state'], $state)) {
    http_response_code(400);
    exit('OAuth state không hợp lệ.');
}

unset($_SESSION['oauth_state']);

$tokenCh = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($tokenCh, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_POSTFIELDS => http_build_query([
        'code' => $code,
        'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'redirect_uri' => $config['redirect_uri'],
        'grant_type' => 'authorization_code',
    ]),
]);

$tokenResponse = curl_exec($tokenCh);
$tokenHttpCode = (int) curl_getinfo($tokenCh, CURLINFO_HTTP_CODE);
curl_close($tokenCh);

if ($tokenHttpCode !== 200 || !$tokenResponse) {
    http_response_code(400);
    exit('Không lấy được access token từ Google.');
}

$tokenData = json_decode($tokenResponse, true);
$accessToken = (string) ($tokenData['access_token'] ?? '');

if ($accessToken === '') {
    http_response_code(400);
    exit('Google access token không hợp lệ.');
}

$userCh = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
curl_setopt_array($userCh, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
]);

$userResponse = curl_exec($userCh);
$userHttpCode = (int) curl_getinfo($userCh, CURLINFO_HTTP_CODE);
curl_close($userCh);

if ($userHttpCode !== 200 || !$userResponse) {
    http_response_code(400);
    exit('Không lấy được thông tin người dùng từ Google.');
}

$googleUser = json_decode($userResponse, true);
$googleId = (string) ($googleUser['id'] ?? '');
$email = trim((string) ($googleUser['email'] ?? ''));
$name = trim((string) ($googleUser['name'] ?? 'Google User'));

if ($googleId === '' || $email === '') {
    http_response_code(400);
    exit('Thông tin Google user không hợp lệ.');
}

$stmt = $pdo->prepare('SELECT id, username, email, role FROM users WHERE google_id = :google_id OR email = :email LIMIT 1');
$stmt->execute([
    'google_id' => $googleId,
    'email' => $email,
]);
$existingUser = $stmt->fetch();

if ($existingUser) {
    $updateStmt = $pdo->prepare('UPDATE users SET google_id = :google_id WHERE id = :id');
    $updateStmt->execute([
        'google_id' => $googleId,
        'id' => (int) $existingUser['id'],
    ]);

    login_user($existingUser);
} else {
    $usernameBase = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($name));
    $username = $usernameBase !== '' ? $usernameBase : 'google_user';

    $suffix = 1;
    $checkStmt = $pdo->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
    while (true) {
        $checkStmt->execute(['username' => $username]);
        if (!$checkStmt->fetch()) {
            break;
        }

        $suffix++;
        $username = $usernameBase . '_' . $suffix;
    }

    $insertStmt = $pdo->prepare(
        'INSERT INTO users (username, password, email, google_id, role)
         VALUES (:username, :password, :email, :google_id, :role)'
    );
    $insertStmt->execute([
        'username' => $username,
        'password' => password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT),
        'email' => $email,
        'google_id' => $googleId,
        'role' => 'customer',
    ]);

    login_user([
        'id' => (int) $pdo->lastInsertId(),
        'username' => $username,
        'email' => $email,
        'role' => 'customer',
    ]);
}

header('Location: /index.php');
exit;

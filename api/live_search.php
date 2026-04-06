<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$q = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($q) < 2) {
    echo json_encode(['ok' => true, 'items' => []]);
    exit;
}

$stmt = $pdo->prepare(
    'SELECT id, name, price, image_url
     FROM products
     WHERE name LIKE :keyword OR description LIKE :keyword
     ORDER BY created_at DESC
     LIMIT 8'
);
$stmt->execute(['keyword' => '%' . $q . '%']);

echo json_encode([
    'ok' => true,
    'items' => $stmt->fetchAll(),
]);

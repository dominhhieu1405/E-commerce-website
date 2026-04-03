<?php

declare(strict_types=1);

session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
$product = $payload['product'] ?? null;

if (!is_array($product) || empty($product['id'])) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Invalid product']);
    exit;
}

$_SESSION['cart'] ??= [];
$productId = (int) $product['id'];

if (isset($_SESSION['cart'][$productId])) {
    $_SESSION['cart'][$productId]['quantity']++;
} else {
    $_SESSION['cart'][$productId] = [
        'id' => $productId,
        'name' => (string) ($product['name'] ?? ''),
        'price' => (float) ($product['price'] ?? 0),
        'quantity' => 1,
    ];
}

echo json_encode(['ok' => true, 'cart' => array_values($_SESSION['cart'])]);

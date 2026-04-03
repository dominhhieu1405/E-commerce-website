<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = (int) current_user()['id'];
$method = $_SERVER['REQUEST_METHOD'];

$getCartId = function () use ($pdo, $userId): int {
    $cartStmt = $pdo->prepare('SELECT id FROM carts WHERE user_id = :user_id LIMIT 1');
    $cartStmt->execute(['user_id' => $userId]);
    $cart = $cartStmt->fetch();

    if ($cart) {
        return (int) $cart['id'];
    }

    $insertCart = $pdo->prepare('INSERT INTO carts (user_id) VALUES (:user_id)');
    $insertCart->execute(['user_id' => $userId]);
    return (int) $pdo->lastInsertId();
};

$cartId = $getCartId();

if ($method === 'GET') {
    $itemsStmt = $pdo->prepare(
        'SELECT ci.product_id AS id, ci.variant_id, ci.quantity, ci.price,
                p.name, p.image_url AS image, p.stock
         FROM cart_items ci
         INNER JOIN products p ON p.id = ci.product_id
         WHERE ci.cart_id = :cart_id'
    );
    $itemsStmt->execute(['cart_id' => $cartId]);
    echo json_encode(['ok' => true, 'items' => $itemsStmt->fetchAll()]);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
$action = (string) ($payload['action'] ?? '');

if ($action === 'add') {
    $productId = (int) ($payload['product_id'] ?? 0);
    $variantId = isset($payload['variant_id']) ? (int) $payload['variant_id'] : null;
    $quantity = max(1, (int) ($payload['quantity'] ?? 1));

    $productStmt = $pdo->prepare('SELECT id, price FROM products WHERE id = :id LIMIT 1');
    $productStmt->execute(['id' => $productId]);
    $product = $productStmt->fetch();
    if (!$product) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Product not found']);
        exit;
    }

    $existsStmt = $pdo->prepare('SELECT id, quantity FROM cart_items WHERE cart_id = :cart_id AND product_id = :product_id AND ((variant_id IS NULL AND :variant_id IS NULL) OR variant_id = :variant_id) LIMIT 1');
    $existsStmt->execute(['cart_id' => $cartId, 'product_id' => $productId, 'variant_id' => $variantId]);
    $exists = $existsStmt->fetch();

    if ($exists) {
        $upd = $pdo->prepare('UPDATE cart_items SET quantity = quantity + :quantity WHERE id = :id');
        $upd->execute(['quantity' => $quantity, 'id' => (int) $exists['id']]);
    } else {
        $ins = $pdo->prepare('INSERT INTO cart_items (cart_id, product_id, variant_id, quantity, price) VALUES (:cart_id, :product_id, :variant_id, :quantity, :price)');
        $ins->execute([
            'cart_id' => $cartId,
            'product_id' => $productId,
            'variant_id' => $variantId,
            'quantity' => $quantity,
            'price' => (float) $product['price'],
        ]);
    }

    echo json_encode(['ok' => true]);
    exit;
}

if ($action === 'clear') {
    $clearStmt = $pdo->prepare('DELETE FROM cart_items WHERE cart_id = :cart_id');
    $clearStmt->execute(['cart_id' => $cartId]);
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(422);
echo json_encode(['ok' => false, 'message' => 'Unsupported action']);

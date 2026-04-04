<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if (!is_logged_in()) {
    $_SESSION['cart'] ??= [];

    if ($method === 'GET') {
        echo json_encode(['ok' => true, 'items' => array_values($_SESSION['cart'])]);
        exit;
    }

    $payload = json_decode(file_get_contents('php://input'), true);
    $action = (string) ($payload['action'] ?? '');

    if ($action === 'add') {
        $productId = (int) ($payload['product_id'] ?? 0);
        $quantity = max(1, (int) ($payload['quantity'] ?? 1));
        $variantId = isset($payload['variant_id']) ? (int) $payload['variant_id'] : null;

        $productStmt = $pdo->prepare('SELECT id, name, price, image_url, stock FROM products WHERE id = :id LIMIT 1');
        $productStmt->execute(['id' => $productId]);
        $product = $productStmt->fetch();

        if (!$product) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'message' => 'Product not found']);
            exit;
        }

        $variantName = null;
        $additionalPrice = 0.0;
        if ($variantId) {
            $variantStmt = $pdo->prepare('SELECT id, variant_name, additional_price, stock FROM product_variants WHERE id = :id AND product_id = :product_id LIMIT 1');
            $variantStmt->execute(['id' => $variantId, 'product_id' => $productId]);
            $variant = $variantStmt->fetch();
            if (!$variant) {
                http_response_code(404);
                echo json_encode(['ok' => false, 'message' => 'Variant not found']);
                exit;
            }
            $variantName = (string) $variant['variant_name'];
            $additionalPrice = (float) $variant['additional_price'];
        }

        $key = $productId . '-' . ($variantId ?? 'base');
        if (isset($_SESSION['cart'][$key])) {
            $_SESSION['cart'][$key]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$key] = [
                'id' => $productId,
                'variant_id' => $variantId,
                'variant_name' => $variantName,
                'name' => (string) $product['name'],
                'price' => (float) $product['price'] + $additionalPrice,
                'image' => (string) ($product['image_url'] ?? ''),
                'quantity' => $quantity,
            ];
        }

        echo json_encode(['ok' => true, 'items' => array_values($_SESSION['cart'])]);
        exit;
    }

    if ($action === 'clear') {
        $_SESSION['cart'] = [];
        echo json_encode(['ok' => true]);
        exit;
    }

    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Unsupported action']);
    exit;
}

$userId = (int) current_user()['id'];

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
                p.name, p.image_url AS image, p.stock, pv.variant_name
         FROM cart_items ci
         INNER JOIN products p ON p.id = ci.product_id
         LEFT JOIN product_variants pv ON pv.id = ci.variant_id
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

    $additionalPrice = 0.0;
    if ($variantId) {
        $variantStmt = $pdo->prepare('SELECT additional_price FROM product_variants WHERE id = :id AND product_id = :product_id LIMIT 1');
        $variantStmt->execute(['id' => $variantId, 'product_id' => $productId]);
        $variant = $variantStmt->fetch();
        if (!$variant) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'message' => 'Variant not found']);
            exit;
        }
        $additionalPrice = (float) $variant['additional_price'];
    }

    $existsStmt = $pdo->prepare(
        'SELECT id, quantity 
        FROM cart_items 
        WHERE cart_id = :cart_id 
        AND product_id = :product_id 
        AND (
            (variant_id IS NULL AND :variant_id_null IS NULL)
            OR variant_id = :variant_id_match
        )
        LIMIT 1'
    );
    $existsStmt->execute([
        'cart_id' => $cartId,
        'product_id' => $productId,
        'variant_id_null' => $variantId,
        'variant_id_match' => $variantId
    ]);
    $exists = $existsStmt->fetch();

    if ($exists) {
        $upd = $pdo->prepare('UPDATE cart_items SET quantity = quantity + :quantity, price = :price WHERE id = :id');
        $upd->execute(['quantity' => $quantity, 'price' => (float) $product['price'] + $additionalPrice, 'id' => (int) $exists['id']]);
    } else {
        $ins = $pdo->prepare('INSERT INTO cart_items (cart_id, product_id, variant_id, quantity, price) VALUES (:cart_id, :product_id, :variant_id, :quantity, :price)');
        $ins->execute([
            'cart_id' => $cartId,
            'product_id' => $productId,
            'variant_id' => $variantId,
            'quantity' => $quantity,
            'price' => (float) $product['price'] + $additionalPrice,
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

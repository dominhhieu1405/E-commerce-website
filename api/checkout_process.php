<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

if (!validate_csrf((string) ($_POST['csrf_token'] ?? ''))) {
    http_response_code(422);
    exit('CSRF token không hợp lệ.');
}

$customerName = trim((string) ($_POST['customer_name'] ?? ''));
$customerEmail = trim((string) ($_POST['customer_email'] ?? ''));
$customerPhone = trim((string) ($_POST['customer_phone'] ?? ''));
$shippingAddress = trim((string) ($_POST['shipping_address'] ?? ''));
$cartJson = (string) ($_POST['cart_json'] ?? '[]');
$cartItems = json_decode($cartJson, true);

if (!$customerName || !$customerEmail || !$customerPhone || !$shippingAddress || !is_array($cartItems) || count($cartItems) === 0) {
    http_response_code(422);
    exit('Thiếu dữ liệu thanh toán hoặc giỏ hàng trống.');
}

try {
    $pdo->beginTransaction();

    $totalPrice = 0.0;
    $validatedItems = [];
    $checkProductStmt = $pdo->prepare('SELECT id, price, stock FROM products WHERE id = :id FOR UPDATE');

    foreach ($cartItems as $item) {
        $productId = (int) ($item['id'] ?? 0);
        $quantity = (int) ($item['quantity'] ?? 0);

        if ($productId <= 0 || $quantity <= 0) {
            throw new RuntimeException('Dữ liệu giỏ hàng không hợp lệ.');
        }

        $checkProductStmt->execute(['id' => $productId]);
        $product = $checkProductStmt->fetch();

        if (!$product) {
            throw new RuntimeException('Sản phẩm không tồn tại.');
        }

        if ((int) $product['stock'] < $quantity) {
            throw new RuntimeException('Sản phẩm không đủ tồn kho.');
        }

        $linePrice = (float) $product['price'];
        $totalPrice += $linePrice * $quantity;

        $validatedItems[] = [
            'product_id' => (int) $product['id'],
            'quantity' => $quantity,
            'price' => $linePrice,
        ];
    }

    $orderStmt = $pdo->prepare(
        'INSERT INTO orders (user_id, total_price, status, customer_name, customer_email, customer_phone, shipping_address)
         VALUES (:user_id, :total_price, :status, :customer_name, :customer_email, :customer_phone, :shipping_address)'
    );

    $orderStmt->execute([
        'user_id' => (int) (current_user()['id'] ?? 0),
        'total_price' => $totalPrice,
        'status' => 'pending',
        'customer_name' => $customerName,
        'customer_email' => $customerEmail,
        'customer_phone' => $customerPhone,
        'shipping_address' => $shippingAddress,
    ]);

    $orderId = (int) $pdo->lastInsertId();

    $orderItemStmt = $pdo->prepare(
        'INSERT INTO order_items (order_id, product_id, quantity, price)
         VALUES (:order_id, :product_id, :quantity, :price)'
    );

    $stockUpdateStmt = $pdo->prepare('UPDATE products SET stock = stock - :quantity WHERE id = :id');

    foreach ($validatedItems as $validatedItem) {
        $orderItemStmt->execute([
            'order_id' => $orderId,
            'product_id' => $validatedItem['product_id'],
            'quantity' => $validatedItem['quantity'],
            'price' => $validatedItem['price'],
        ]);

        $stockUpdateStmt->execute([
            'quantity' => $validatedItem['quantity'],
            'id' => $validatedItem['product_id'],
        ]);
    }

    $pdo->commit();

    echo '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><script>localStorage.removeItem("minimal_store_cart");</script></head><body style="font-family: sans-serif; padding: 24px;">';
    echo '<h2>Đặt hàng thành công!</h2>';
    echo '<p>Mã đơn hàng #' . $orderId . ' đã được tạo.</p>';
    echo '<p><a href="/index.php">Tiếp tục mua sắm</a></p>';
    echo '</body></html>';
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(400);
    exit('Checkout thất bại: ' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8'));
}

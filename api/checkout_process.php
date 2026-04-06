<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

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
$shippingMethod = (string) ($_POST['shipping_method'] ?? 'standard');
$shippingMethodMap = [
    'slow' => ['label' => 'Giao hàng chậm', 'fee' => 1.5],
    'standard' => ['label' => 'Giao hàng bình thường', 'fee' => 3.0],
    'fast' => ['label' => 'Giao hàng nhanh', 'fee' => 6.0],
    'express' => ['label' => 'Giao hàng hỏa tốc', 'fee' => 10.0],
];
if (!isset($shippingMethodMap[$shippingMethod])) {
    $shippingMethod = 'standard';
}
$shippingLabel = $shippingMethodMap[$shippingMethod]['label'];
$shippingFee = (float) $shippingMethodMap[$shippingMethod]['fee'];
$paymentMethod = (string) ($_POST['payment_method'] ?? 'cod');
$allowedPaymentMethods = ['visa', 'bank_transfer', 'cod'];
if (!in_array($paymentMethod, $allowedPaymentMethods, true)) {
    $paymentMethod = 'cod';
}

$cartJson = (string) ($_POST['cart_json'] ?? '[]');
$cartItems = json_decode($cartJson, true);
if (!is_array($cartItems)) {
    $cartItems = [];
}

$userId = is_logged_in() ? (int) (current_user()['id'] ?? 0) : null;
if (count($cartItems) === 0) {
    if ($userId) {
        $cartStmt = $pdo->prepare('SELECT ci.product_id AS id, ci.variant_id, ci.quantity FROM carts c INNER JOIN cart_items ci ON ci.cart_id = c.id WHERE c.user_id = :user_id');
        $cartStmt->execute(['user_id' => $userId]);
        $cartItems = $cartStmt->fetchAll();
    }
}

if (!$customerName || !$customerEmail || !$customerPhone || !$shippingAddress || count($cartItems) === 0) {
    http_response_code(422);
    exit('Thiếu dữ liệu thanh toán hoặc giỏ hàng trống.');
}

try {
    $pdo->beginTransaction();

    $totalPrice = 0.0;
    $validatedItems = [];
    $checkProductStmt = $pdo->prepare('SELECT id, price, stock FROM products WHERE id = :id FOR UPDATE');
    $checkVariantStmt = $pdo->prepare('SELECT id, additional_price, stock FROM product_variants WHERE id = :id AND product_id = :product_id FOR UPDATE');

    foreach ($cartItems as $item) {
        $productId = (int) ($item['id'] ?? 0);
        $quantity = (int) ($item['quantity'] ?? 0);
        $variantId = isset($item['variant_id']) ? (int) $item['variant_id'] : (isset($item['variantId']) ? (int) $item['variantId'] : null);

        if ($productId <= 0 || $quantity <= 0) {
            throw new RuntimeException('Dữ liệu giỏ hàng không hợp lệ.');
        }

        $checkProductStmt->execute(['id' => $productId]);
        $product = $checkProductStmt->fetch();

        if (!$product || (int) $product['stock'] < $quantity) {
            throw new RuntimeException('Sản phẩm không tồn tại hoặc không đủ tồn kho.');
        }

        $additionalPrice = 0.0;
        if ($variantId) {
            $checkVariantStmt->execute(['id' => $variantId, 'product_id' => $productId]);
            $variant = $checkVariantStmt->fetch();
            if (!$variant || (int) $variant['stock'] < $quantity) {
                throw new RuntimeException('Biến thể không tồn tại hoặc không đủ tồn kho.');
            }
            $additionalPrice = (float) $variant['additional_price'];
        }

        $linePrice = (float) $product['price'] + $additionalPrice;
        $totalPrice += $linePrice * $quantity;

        $validatedItems[] = [
            'product_id' => (int) $product['id'],
            'variant_id' => $variantId,
            'quantity' => $quantity,
            'price' => $linePrice,
        ];
    }

    $orderStmt = $pdo->prepare(
        'INSERT INTO orders (user_id, total_price, payment_method, payment_status, status, customer_name, customer_email, customer_phone, shipping_address)
         VALUES (:user_id, :total_price, :payment_method, :payment_status, :status, :customer_name, :customer_email, :customer_phone, :shipping_address)'
    );

    $orderStmt->execute([
        'user_id' => $userId,
        'total_price' => $totalPrice + $shippingFee,
        'payment_method' => $paymentMethod,
        'payment_status' => $paymentMethod === 'cod' ? 'unpaid' : 'unpaid',
        'status' => $paymentMethod === 'cod' ? 'pending' : 'pending',
        'customer_name' => $customerName,
        'customer_email' => $customerEmail,
        'customer_phone' => $customerPhone,
        'shipping_address' => $shippingAddress . ' | ' . $shippingLabel,
    ]);

    $orderId = (int) $pdo->lastInsertId();

    $orderItemStmt = $pdo->prepare(
        'INSERT INTO order_items (order_id, product_id, variant_id, quantity, price)
         VALUES (:order_id, :product_id, :variant_id, :quantity, :price)'
    );

    $stockUpdateStmt = $pdo->prepare('UPDATE products SET stock = stock - :quantity WHERE id = :id');
    $variantStockUpdateStmt = $pdo->prepare('UPDATE product_variants SET stock = stock - :quantity WHERE id = :id');

    foreach ($validatedItems as $validatedItem) {
        $orderItemStmt->execute([
            'order_id' => $orderId,
            'product_id' => $validatedItem['product_id'],
            'variant_id' => $validatedItem['variant_id'],
            'quantity' => $validatedItem['quantity'],
            'price' => $validatedItem['price'],
        ]);

        $stockUpdateStmt->execute([
            'quantity' => $validatedItem['quantity'],
            'id' => $validatedItem['product_id'],
        ]);

        if (!empty($validatedItem['variant_id'])) {
            $variantStockUpdateStmt->execute([
                'quantity' => $validatedItem['quantity'],
                'id' => $validatedItem['variant_id'],
            ]);
        }
    }

    if ($userId) {
        $saveProfileStmt = $pdo->prepare('UPDATE users SET full_name = :full_name, phone = :phone, address = :address WHERE id = :id');
        $saveProfileStmt->execute([
            'full_name' => $customerName,
            'phone' => $customerPhone,
            'address' => $shippingAddress,
            'id' => $userId,
        ]);

        $clearCart = $pdo->prepare('DELETE ci FROM cart_items ci INNER JOIN carts c ON c.id = ci.cart_id WHERE c.user_id = :user_id');
        $clearCart->execute(['user_id' => $userId]);
    }

    $pdo->commit();

    if ($paymentMethod === 'visa') {
        header('Location: /payment/visa.php?order_id=' . $orderId);
    } elseif ($paymentMethod === 'bank_transfer') {
        header('Location: /payment/bank_transfer.php?order_id=' . $orderId);
    } else {
        header('Location: /order_success.php?order_id=' . $orderId);
    }
    exit;
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(400);
    exit('Checkout thất bại: ' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8'));
}

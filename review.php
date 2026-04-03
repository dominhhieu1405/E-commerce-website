<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

$user = current_user();
$orderId = (int) ($_GET['order_id'] ?? $_POST['order_id'] ?? 0);
$pageTitle = 'Đánh giá đơn hàng';
$message = '';

$orderStmt = $pdo->prepare('SELECT id, user_id FROM orders WHERE id = :id LIMIT 1');
$orderStmt->execute(['id' => $orderId]);
$order = $orderStmt->fetch();

if (!$order || (int) $order['user_id'] !== (int) $user['id']) {
    http_response_code(403);
    exit('Không có quyền đánh giá đơn này.');
}

$existsStmt = $pdo->prepare('SELECT id FROM reviews WHERE order_id = :order_id LIMIT 1');
$existsStmt->execute(['order_id' => $orderId]);
if ($existsStmt->fetch()) {
    header('Location: /user/orders.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf((string) ($_POST['csrf_token'] ?? ''))) {
        $message = 'CSRF token không hợp lệ.';
    } else {
        $rating = (int) ($_POST['rating'] ?? 0);
        $comment = trim((string) ($_POST['comment'] ?? ''));
        if ($rating < 0 || $rating > 5 || $comment === '') {
            $message = 'Vui lòng nhập đánh giá hợp lệ (0-5 sao).';
        } else {
            $insert = $pdo->prepare('INSERT INTO reviews (order_id, user_id, rating, comment) VALUES (:order_id, :user_id, :rating, :comment)');
            $insert->execute([
                'order_id' => $orderId,
                'user_id' => (int) $user['id'],
                'rating' => $rating,
                'comment' => $comment,
            ]);
            header('Location: /user/orders.php');
            exit;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<section class="max-w-xl mx-auto px-4 py-8">
  <div class="bg-white rounded-lg border border-gray-200 p-6 space-y-4">
    <h1 class="text-2xl font-bold">Đánh giá đơn #<?= (int) $orderId; ?></h1>
    <?php if ($message): ?><p class="text-sm text-red-700 bg-red-50 rounded p-2"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
    <form method="post" class="space-y-3">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>" />
      <input type="hidden" name="order_id" value="<?= (int) $orderId; ?>" />
      <div>
        <label class="text-sm">Số sao (0-5)</label>
        <input type="number" name="rating" min="0" max="5" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" />
      </div>
      <div>
        <label class="text-sm">Nhận xét</label>
        <textarea name="comment" required rows="4" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2"></textarea>
      </div>
      <button class="rounded-lg bg-black text-white px-4 py-2 hover:opacity-80 transition">Gửi đánh giá</button>
    </form>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

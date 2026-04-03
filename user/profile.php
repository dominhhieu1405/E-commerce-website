<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pageTitle = 'Tài khoản của tôi';
$user = current_user();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf((string) ($_POST['csrf_token'] ?? ''))) {
        $message = 'CSRF token không hợp lệ.';
    } else {
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));

        $updateStmt = $pdo->prepare('UPDATE users SET full_name = :full_name, phone = :phone, address = :address WHERE id = :id');
        $updateStmt->execute([
            'full_name' => $fullName,
            'phone' => $phone,
            'address' => $address,
            'id' => (int) $user['id'],
        ]);

        $message = 'Đã cập nhật hồ sơ.';
    }
}

$userStmt = $pdo->prepare('SELECT username, email, full_name, phone, address FROM users WHERE id = :id LIMIT 1');
$userStmt->execute(['id' => (int) $user['id']]);
$profile = $userStmt->fetch() ?: [];

require_once __DIR__ . '/../includes/header.php';
?>
<section class="max-w-3xl mx-auto px-4 py-8">
  <div class="bg-white rounded-lg border border-gray-200 p-6 space-y-4">
    <h1 class="text-2xl font-bold">Thông tin tài khoản</h1>
    <?php if ($message): ?><p class="text-sm text-green-700 bg-green-50 rounded p-2"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>

    <form method="post" class="grid md:grid-cols-2 gap-4">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>" />
      <div>
        <label class="text-sm">Username</label>
        <input disabled value="<?= htmlspecialchars((string) ($profile['username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 bg-gray-100" />
      </div>
      <div>
        <label class="text-sm">Email</label>
        <input disabled value="<?= htmlspecialchars((string) ($profile['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 bg-gray-100" />
      </div>
      <div>
        <label class="text-sm">Họ tên</label>
        <input name="full_name" value="<?= htmlspecialchars((string) ($profile['full_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" />
      </div>
      <div>
        <label class="text-sm">Số điện thoại</label>
        <input name="phone" value="<?= htmlspecialchars((string) ($profile['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" />
      </div>
      <div class="md:col-span-2">
        <label class="text-sm">Địa chỉ</label>
        <textarea name="address" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2"><?= htmlspecialchars((string) ($profile['address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
      </div>
      <div class="md:col-span-2">
        <button class="rounded-lg bg-black text-white px-4 py-2 hover:opacity-80 transition">Lưu thay đổi</button>
      </div>
    </form>
  </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

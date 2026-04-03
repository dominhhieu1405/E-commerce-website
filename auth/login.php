<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

$googleConfig = require __DIR__ . '/../config/google_oauth.php';
$pageTitle = 'Đăng nhập';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf((string) ($_POST['csrf_token'] ?? ''))) {
        $error = 'Phiên làm việc không hợp lệ. Vui lòng thử lại.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $userStmt = $pdo->prepare('SELECT id, username, email, role, password FROM users WHERE email = :email LIMIT 1');
        $userStmt->execute(['email' => $email]);
        $user = $userStmt->fetch();

        if (!$user || !password_verify($password, (string) $user['password'])) {
            $error = 'Sai thông tin đăng nhập.';
        } else {
            login_user($user);
            header('Location: /index.php');
            exit;
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<section class="max-w-md mx-auto px-4 py-10">
  <div class="bg-white rounded-lg border border-gray-200 p-6 space-y-4">
    <h1 class="text-2xl font-bold">Đăng nhập</h1>

    <?php if ($error): ?>
      <div class="rounded-lg bg-red-50 text-red-700 p-3 text-sm"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" class="space-y-4">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>" />
      <div>
        <label class="text-sm">Email</label>
        <input type="email" name="email" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" />
      </div>
      <div>
        <label class="text-sm">Mật khẩu</label>
        <input type="password" name="password" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" />
      </div>
      <button class="w-full rounded-lg bg-black text-white px-4 py-2 hover:opacity-80 transition">Đăng nhập</button>
    </form>

    <a
      href="/auth/google_login.php"
      class="block text-center rounded-lg border border-gray-300 px-4 py-2 hover:opacity-80 transition"
    >
      Đăng nhập với Google
    </a>

    <?php if ($googleConfig['client_id'] === 'YOUR_GOOGLE_CLIENT_ID'): ?>
      <p class="text-xs text-gray-500">* Cấu hình GOOGLE_CLIENT_ID/GOOGLE_CLIENT_SECRET để dùng đăng nhập Google.</p>
    <?php endif; ?>

    <p class="text-sm text-gray-600">Chưa có tài khoản? <a href="/auth/register.php" class="underline">Đăng ký</a></p>
  </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

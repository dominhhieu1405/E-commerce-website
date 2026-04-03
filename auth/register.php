<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Đăng ký';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf((string) ($_POST['csrf_token'] ?? ''))) {
        $error = 'Phiên làm việc không hợp lệ. Vui lòng thử lại.';
    } else {
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || $email === '' || $password === '') {
            $error = 'Vui lòng nhập đầy đủ thông tin.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email không hợp lệ.';
        } elseif (strlen($password) < 8) {
            $error = 'Mật khẩu cần tối thiểu 8 ký tự.';
        } else {
            $existsStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email OR username = :username LIMIT 1');
            $existsStmt->execute([
                'email' => $email,
                'username' => $username,
            ]);

            if ($existsStmt->fetch()) {
                $error = 'Email hoặc username đã tồn tại.';
            } else {
                $insertStmt = $pdo->prepare(
                    'INSERT INTO users (username, password, email, role) VALUES (:username, :password, :email, :role)'
                );
                $insertStmt->execute([
                    'username' => $username,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'email' => $email,
                    'role' => 'customer',
                ]);

                $userId = (int) $pdo->lastInsertId();
                login_user([
                    'id' => $userId,
                    'username' => $username,
                    'email' => $email,
                    'role' => 'customer',
                ]);

                header('Location: /index.php');
                exit;
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<section class="max-w-md mx-auto px-4 py-10">
  <div class="bg-white rounded-lg border border-gray-200 p-6 space-y-4">
    <h1 class="text-2xl font-bold">Tạo tài khoản</h1>

    <?php if ($error): ?>
      <div class="rounded-lg bg-red-50 text-red-700 p-3 text-sm"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" class="space-y-4">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>" />
      <div>
        <label class="text-sm">Username</label>
        <input name="username" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" />
      </div>
      <div>
        <label class="text-sm">Email</label>
        <input type="email" name="email" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" />
      </div>
      <div>
        <label class="text-sm">Mật khẩu</label>
        <input type="password" name="password" minlength="8" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" />
      </div>
      <button class="w-full rounded-lg bg-black text-white px-4 py-2 hover:opacity-80 transition">Đăng ký</button>
    </form>

    <p class="text-sm text-gray-600">Đã có tài khoản? <a href="/auth/login.php" class="underline">Đăng nhập</a></p>
  </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

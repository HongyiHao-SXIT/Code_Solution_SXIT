<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/entity/User.php';
require_once __DIR__ . '/auth.php';

startSession();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account = trim((string) ($_POST['account'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    if ($account === '' || $password === '' || $confirm === '') {
        $error = '所有字段都必须填写。';
    } elseif ($password !== $confirm) {
        $error = '两次输入的密码不一致。';
    } else {
        try {
            // Validate via User entity (throws InvalidArgumentException on failure)
            $user = new User(0, $account, $password, 'student');

            // Check if account already exists
            $pdo = db();
            $stmt = $pdo->prepare("SELECT id FROM users WHERE account = :account");
            $stmt->execute([':account' => $user->getAccount()]);
            if ($stmt->fetch()) {
                $error = '该账号已被注册，请更换账号。';
            } else {
                // Insert
                $insert = $pdo->prepare(
                    "INSERT INTO users (account, password_hash, role) VALUES (:account, :password_hash, :role)"
                );
                $insert->execute([
                    ':account' => $user->getAccount(),
                    ':password_hash' => $user->getPassword(),
                    ':role' => 'student',
                ]);

                $newId = (int) $pdo->lastInsertId();

                // Auto login
                loginUser([
                    'id' => $newId,
                    'account' => $user->getAccount(),
                    'role' => 'student',
                ]);

                header('Location: index.php');
                exit;
            }
        } catch (InvalidArgumentException $e) {
            $error = $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>注册 - UniData</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center">
<div class="bg-white rounded-2xl shadow-lg p-8 w-full max-w-md">
    <div class="text-center mb-6">
        <h1 class="text-2xl font-bold text-sky-700">
            <i class="fa-solid fa-user-plus mr-2"></i>注册 UniData
        </h1>
        <p class="text-sm text-slate-500 mt-1">创建账号，开始探索全球大学招生信息</p>
    </div>

    <?php if ($error !== ''): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-3 mb-4 text-sm">
            <i class="fa-solid fa-circle-exclamation mr-1"></i><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <form method="post" class="space-y-4">
        <div>
            <label class="text-sm text-slate-600">账号</label>
            <input
                type="text"
                name="account"
                value="<?php echo htmlspecialchars($_POST['account'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                placeholder="6-15位字母、数字或下划线"
                required
                class="mt-1 w-full rounded-lg border-slate-300 focus:border-sky-500 focus:ring-sky-500"
            >
        </div>

        <div>
            <label class="text-sm text-slate-600">密码</label>
            <input
                type="password"
                name="password"
                placeholder="至少8位，含大小写字母和数字"
                required
                class="mt-1 w-full rounded-lg border-slate-300 focus:border-sky-500 focus:ring-sky-500"
            >
        </div>

        <div>
            <label class="text-sm text-slate-600">确认密码</label>
            <input
                type="password"
                name="confirm_password"
                placeholder="再次输入密码"
                required
                class="mt-1 w-full rounded-lg border-slate-300 focus:border-sky-500 focus:ring-sky-500"
            >
        </div>

        <button class="w-full py-2 rounded-lg bg-sky-600 text-white hover:bg-sky-700 transition font-medium">
            注册
        </button>
    </form>

    <p class="text-center text-sm text-slate-500 mt-4">
        已有账号？<a href="login.php" class="text-sky-600 hover:underline">立即登录</a>
    </p>
</div>
</body>
</html>
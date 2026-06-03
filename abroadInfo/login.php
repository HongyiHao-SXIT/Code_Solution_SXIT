<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

startSession();

// If already logged in, redirect to index
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$redirect = (string) ($_GET['redirect'] ?? 'index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account = trim((string) ($_POST['account'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($account === '' || $password === '') {
        $error = '请输入账号和密码。';
    } else {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT id, account, password_hash, role FROM users WHERE account = :account");
        $stmt->execute([':account' => $account]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            loginUser($user);

            // Sanitize redirect to prevent open redirect attacks
            $redirect = preg_match('/^[a-zA-Z0-9_.\/\-?=&]+$/', $redirect) ? $redirect : 'index.php';
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = '账号或密码错误。';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - UniData</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center">
<div class="bg-white rounded-2xl shadow-lg p-8 w-full max-w-md">
    <div class="text-center mb-6">
        <h1 class="text-2xl font-bold text-sky-700">
            <i class="fa-solid fa-right-to-bracket mr-2"></i>登录 UniData
        </h1>
        <p class="text-sm text-slate-500 mt-1">登录以管理你的留学信息</p>
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
                placeholder="请输入账号"
                required
                class="mt-1 w-full rounded-lg border-slate-300 focus:border-sky-500 focus:ring-sky-500"
            >
        </div>

        <div>
            <label class="text-sm text-slate-600">密码</label>
            <input
                type="password"
                name="password"
                placeholder="请输入密码"
                required
                class="mt-1 w-full rounded-lg border-slate-300 focus:border-sky-500 focus:ring-sky-500"
            >
        </div>

        <button class="w-full py-2 rounded-lg bg-sky-600 text-white hover:bg-sky-700 transition font-medium">
            登录
        </button>
    </form>

    <p class="text-center text-sm text-slate-500 mt-4">
        还没有账号？<a href="register.php" class="text-sky-600 hover:underline">立即注册</a>
    </p>
</div>
</body>
</html>
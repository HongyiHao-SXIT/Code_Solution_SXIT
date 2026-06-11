<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

startSession();

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
    <title>登录 - UniData 留学数据平台</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            200: '#c7d2fe',
                            300: '#a5b4fc',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                        }
                    },
                    fontFamily: {
                        sans: ['"Inter"', '"PingFang SC"', '"Microsoft YaHei"', 'system-ui', 'sans-serif'],
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'fade-in': 'fadeIn 0.6s ease-out',
                        'slide-up': 'slideUp 0.5s ease-out',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-20px)' },
                        },
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        },
                        slideUp: {
                            '0%': { opacity: '0', transform: 'translateY(20px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen bg-gradient-to-br from-brand-50 via-white to-blue-50 font-sans antialiased">
    <!-- Decorative background elements -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-brand-200/30 rounded-full blur-3xl animate-float"></div>
        <div class="absolute -bottom-32 -left-32 w-80 h-80 bg-blue-200/30 rounded-full blur-3xl animate-float" style="animation-delay: 2s;"></div>
        <div class="absolute top-1/3 left-1/4 w-64 h-64 bg-indigo-200/20 rounded-full blur-3xl animate-float" style="animation-delay: 4s;"></div>
    </div>

    <!-- Main Content -->
    <div class="relative z-10 min-h-screen flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md animate-fade-in">
            <!-- Logo / Brand -->
            <div class="text-center mb-8 animate-slide-up">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-brand-600 to-blue-600 shadow-xl shadow-brand-500/30 mb-5">
                    <i class="fa-solid fa-graduation-cap text-white text-2xl"></i>
                </div>
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">
                    欢迎回到 <span class="bg-gradient-to-r from-brand-600 to-blue-600 bg-clip-text text-transparent">UniData</span>
                </h1>
                <p class="text-slate-500 mt-2 text-sm">登录以管理你的留学信息和申请计划</p>
            </div>

            <!-- Card -->
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl shadow-slate-200/50 border border-white/60 p-8 animate-slide-up" style="animation-delay: 0.15s;">
                <?php if ($error !== ''): ?>
                    <div class="flex items-start gap-3 bg-red-50 border border-red-200 rounded-2xl p-4 mb-6 text-sm animate-fade-in">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                            <i class="fa-solid fa-circle-exclamation text-red-500 text-xs"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-red-800">登录失败</p>
                            <p class="text-red-600 mt-0.5"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="post" class="space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">账号</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-user text-slate-400"></i>
                            </div>
                            <input
                                type="text"
                                name="account"
                                value="<?php echo htmlspecialchars($_POST['account'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                placeholder="请输入账号"
                                required
                                class="w-full pl-11 pr-4 py-3.5 rounded-2xl border-2 border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-500/10 transition-all duration-200 outline-none"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">密码</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-lock text-slate-400"></i>
                            </div>
                            <input
                                type="password"
                                name="password"
                                placeholder="请输入密码"
                                required
                                class="w-full pl-11 pr-4 py-3.5 rounded-2xl border-2 border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-500/10 transition-all duration-200 outline-none"
                            >
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="w-full py-3.5 rounded-2xl bg-gradient-to-r from-brand-600 to-blue-600 text-white font-semibold text-base shadow-lg shadow-brand-500/25 hover:shadow-xl hover:shadow-brand-500/30 hover:from-brand-700 hover:to-blue-700 active:scale-[0.98] transition-all duration-200"
                    >
                        <i class="fa-solid fa-right-to-bracket mr-2"></i>登录
                    </button>
                </form>

                <div class="mt-6 pt-6 border-t border-slate-100">
                    <p class="text-center text-sm text-slate-500">
                        还没有账号？
                        <a href="register.php" class="font-semibold text-brand-600 hover:text-brand-700 transition-colors">
                            立即注册 <i class="fa-solid fa-arrow-right text-xs ml-1"></i>
                        </a>
                    </p>
                    <p class="text-center mt-3">
                        <a href="index.php" class="text-xs text-slate-400 hover:text-slate-600 transition-colors">
                            <i class="fa-solid fa-arrow-left mr-1"></i>返回首页浏览数据
                        </a>
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <p class="text-center text-xs text-slate-400 mt-6 animate-fade-in" style="animation-delay: 0.3s;">
                &copy; <?php echo date('Y'); ?> UniData · 全球留学数据平台
            </p>
        </div>
    </div>
</body>
</html>
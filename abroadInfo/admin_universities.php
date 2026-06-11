<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

requireAdmin();

$pdo = db();
$message = '';
$error = '';

// Handle CRUD actions
$action = trim((string) ($_POST['action'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $country = trim((string) ($_POST['country'] ?? ''));
        $city = trim((string) ($_POST['city'] ?? ''));
        $qsRank = $_POST['qs_rank'] !== '' ? (int) $_POST['qs_rank'] : null;
        $usnewsRank = $_POST['usnews_rank'] !== '' ? (int) $_POST['usnews_rank'] : null;
        $website = trim((string) ($_POST['website'] ?? ''));

        if ($name === '') {
            $error = '大学名称不能为空。';
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO universities (name, country, city, qs_rank, usnews_rank, website) 
                 VALUES (:name, :country, :city, :qs_rank, :usnews_rank, :website)"
            );
            $stmt->execute([
                ':name' => $name,
                ':country' => $country ?: null,
                ':city' => $city ?: null,
                ':qs_rank' => $qsRank,
                ':usnews_rank' => $usnewsRank,
                ':website' => $website ?: null,
            ]);
            $message = "已添加大学: {$name}";
        }
    } elseif ($action === 'edit') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $country = trim((string) ($_POST['country'] ?? ''));
        $city = trim((string) ($_POST['city'] ?? ''));
        $qsRank = $_POST['qs_rank'] !== '' ? (int) $_POST['qs_rank'] : null;
        $usnewsRank = $_POST['usnews_rank'] !== '' ? (int) $_POST['usnews_rank'] : null;
        $website = trim((string) ($_POST['website'] ?? ''));

        if ($id > 0 && $name !== '') {
            $stmt = $pdo->prepare(
                "UPDATE universities SET name=:name, country=:country, city=:city, 
                 qs_rank=:qs_rank, usnews_rank=:usnews_rank, website=:website WHERE id=:id"
            );
            $stmt->execute([
                ':id' => $id,
                ':name' => $name,
                ':country' => $country ?: null,
                ':city' => $city ?: null,
                ':qs_rank' => $qsRank,
                ':usnews_rank' => $usnewsRank,
                ':website' => $website ?: null,
            ]);
            $message = "已更新大学: {$name}";
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("DELETE FROM universities WHERE id = :id")->execute([':id' => $id]);
            $message = "已删除大学 ID: {$id}";
        }
    }
}

// Fetch all universities
$universities = $pdo->query("SELECT * FROM universities ORDER BY name")->fetchAll();

// Country stats
$stats = $pdo->query("SELECT country, COUNT(*) as cnt FROM universities WHERE country IS NOT NULL AND country != '' GROUP BY country ORDER BY cnt DESC")->fetchAll();

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>大学管理 - UniData Admin</title>
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
                        'fade-in': 'fadeIn 0.5s ease-out',
                        'slide-up': 'slideUp 0.4s ease-out',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        },
                        slideUp: {
                            '0%': { opacity: '0', transform: 'translateY(12px)' },
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
<body class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-amber-50/30 font-sans antialiased text-slate-900">

<!-- Navbar -->
<nav class="sticky top-0 z-50 bg-white/80 backdrop-blur-xl border-b border-slate-200/60 shadow-sm shadow-slate-200/20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3.5 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500 to-orange-500 flex items-center justify-center shadow-lg shadow-amber-500/20">
                <i class="fa-solid fa-gear text-white text-lg"></i>
            </div>
            <div>
                <h1 class="text-lg font-extrabold text-slate-900 tracking-tight leading-tight">UniData <span class="text-amber-600">Admin</span></h1>
                <p class="text-xs text-slate-400 leading-tight">大学与项目管理</p>
            </div>
        </div>

        <div class="flex items-center gap-1.5 sm:gap-3">
            <a href="index.php" class="px-3 py-2 rounded-xl text-sm font-medium text-slate-500 hover:text-brand-600 hover:bg-brand-50/50 transition-colors">
                <i class="fa-solid fa-home mr-1.5"></i><span class="hidden sm:inline">前台</span>
            </a>
            <a href="admin_universities.php" class="px-3 py-2 rounded-xl text-sm font-semibold bg-amber-50 text-amber-700 transition-colors">
                <i class="fa-solid fa-building-columns mr-1.5"></i><span class="hidden sm:inline">大学管理</span>
            </a>
            <a href="admin_projects.php" class="px-3 py-2 rounded-xl text-sm font-medium text-slate-500 hover:text-brand-600 hover:bg-brand-50/50 transition-colors">
                <i class="fa-solid fa-list-check mr-1.5"></i><span class="hidden sm:inline">项目管理</span>
            </a>
            <a href="match.php" class="px-3 py-2 rounded-xl text-sm font-medium text-slate-500 hover:text-brand-600 hover:bg-brand-50/50 transition-colors">
                <i class="fa-solid fa-wand-magic-sparkles mr-1.5"></i><span class="hidden sm:inline">匹配</span>
            </a>

            <div class="h-6 w-px bg-slate-200 mx-1"></div>

            <div class="flex items-center gap-2">
                <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-amber-50">
                    <i class="fa-solid fa-user-tie text-amber-600 text-xs"></i>
                    <span class="text-sm font-medium text-slate-700"><?php echo htmlspecialchars(currentUserAccount() ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <a href="logout.php" class="p-2 rounded-xl text-slate-400 hover:text-red-500 hover:bg-red-50 transition-colors" title="退出">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </a>
            </div>
        </div>
    </div>
</nav>

<main class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
    <!-- Messages -->
    <?php if ($message !== ''): ?>
        <div class="flex items-center gap-3 bg-emerald-50 border border-emerald-200 rounded-2xl p-4 mb-5 animate-fade-in">
            <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center">
                <i class="fa-solid fa-check text-emerald-500 text-sm"></i>
            </div>
            <p class="text-emerald-700 font-medium text-sm"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="flex items-center gap-3 bg-red-50 border border-red-200 rounded-2xl p-4 mb-5 animate-fade-in">
            <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                <i class="fa-solid fa-exclamation text-red-500 text-sm"></i>
            </div>
            <p class="text-red-700 font-medium text-sm"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8 animate-fade-in">
        <div class="bg-white/80 backdrop-blur-xl rounded-2xl p-5 border border-slate-200/60 shadow-sm text-center">
            <p class="text-3xl font-extrabold text-brand-700"><?php echo count($universities); ?></p>
            <p class="text-sm text-slate-500 font-medium mt-1">大学总数</p>
        </div>
        <?php foreach (array_slice($stats, 0, 3) as $s): ?>
            <div class="bg-white/80 backdrop-blur-xl rounded-2xl p-5 border border-slate-200/60 shadow-sm text-center">
                <p class="text-3xl font-extrabold text-brand-700"><?php echo (int) $s['cnt']; ?></p>
                <p class="text-sm text-slate-500 font-medium mt-1"><?php echo htmlspecialchars($s['country'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Add / Edit Form -->
        <div class="lg:col-span-1">
            <form method="post" id="universityForm" class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/60 shadow-lg shadow-slate-200/30 p-6 space-y-4 sticky top-28 animate-slide-up">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="formId" value="">
                
                <div class="flex items-center gap-2 mb-1">
                    <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center">
                        <i class="fa-solid fa-plus text-amber-600 text-sm"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-800" id="formTitle">添加大学</h3>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1.5">大学名称 <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="formName" required placeholder="如 MIT" class="w-full px-4 py-2.5 rounded-xl border-2 border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-500/10 transition-all duration-200 outline-none text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1.5">国家</label>
                    <select name="country" id="formCountry" class="w-full py-2.5 pl-4 pr-10 rounded-xl border-2 border-slate-200 bg-slate-50 text-slate-900 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-500/10 transition-all duration-200 outline-none text-sm appearance-none cursor-pointer" style="background-image: url('data:image/svg+xml;charset=UTF-8,%3csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%2394a3b8%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22%3e%3cpolyline points=%226 9 12 15 18 9%22%3e%3c/polyline%3e%3c/svg%3e'); background-repeat: no-repeat; background-position: right 12px center; background-size: 16px;">
                        <option value="">请选择</option>
                        <?php foreach (['英国', '美国', '澳大利亚', '加拿大', '德国', '法国', '日本', '新加坡', '中国'] as $c): ?>
                            <option value="<?php echo $c; ?>"><?php echo $c; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1.5">城市</label>
                    <input type="text" name="city" id="formCity" placeholder="如 Cambridge, MA" class="w-full px-4 py-2.5 rounded-xl border-2 border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-500/10 transition-all duration-200 outline-none text-sm">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-sm font-semibold text-slate-600 mb-1.5">QS排名</label>
                        <input type="number" name="qs_rank" id="formQsRank" placeholder="如 1" class="w-full px-4 py-2.5 rounded-xl border-2 border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-500/10 transition-all duration-200 outline-none text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-600 mb-1.5">US News</label>
                        <input type="number" name="usnews_rank" id="formUsnewsRank" placeholder="如 2" class="w-full px-4 py-2.5 rounded-xl border-2 border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-500/10 transition-all duration-200 outline-none text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1.5">官网</label>
                    <input type="url" name="website" id="formWebsite" placeholder="https://..." class="w-full px-4 py-2.5 rounded-xl border-2 border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-500/10 transition-all duration-200 outline-none text-sm">
                </div>
                <button type="submit" class="w-full py-3 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 text-white font-semibold text-sm shadow-lg shadow-amber-500/20 hover:shadow-xl hover:shadow-amber-500/30 hover:from-amber-600 hover:to-orange-600 active:scale-[0.98] transition-all duration-200" id="formSubmit">
                    <i class="fa-solid fa-plus mr-1.5"></i>添加
                </button>
                <button type="button" id="cancelEditBtn" class="w-full py-2.5 rounded-xl border-2 border-slate-200 text-slate-600 font-medium text-sm hover:bg-slate-50 transition-colors hidden" onclick="resetForm()">
                    取消编辑
                </button>
            </form>
        </div>

        <!-- University List -->
        <div class="lg:col-span-3">
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/60 shadow-lg shadow-slate-200/30 overflow-hidden animate-slide-up">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200">
                                <th class="text-left px-5 py-4 font-bold text-slate-700 text-xs uppercase tracking-wider">名称</th>
                                <th class="text-left px-5 py-4 font-bold text-slate-700 text-xs uppercase tracking-wider">国家</th>
                                <th class="text-left px-5 py-4 font-bold text-slate-700 text-xs uppercase tracking-wider hidden md:table-cell">城市</th>
                                <th class="text-center px-5 py-4 font-bold text-slate-700 text-xs uppercase tracking-wider">QS</th>
                                <th class="text-center px-5 py-4 font-bold text-slate-700 text-xs uppercase tracking-wider hidden md:table-cell">US News</th>
                                <th class="text-right px-5 py-4 font-bold text-slate-700 text-xs uppercase tracking-wider">操作</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if ($universities === []): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-12 text-slate-400">
                                        <div class="flex flex-col items-center gap-2">
                                            <i class="fa-solid fa-building-columns text-3xl text-slate-300"></i>
                                            <p>暂无大学数据，请在左侧添加</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($universities as $u): ?>
                                <tr class="hover:bg-brand-50/30 transition-colors">
                                    <td class="px-5 py-3.5">
                                        <span class="font-semibold text-slate-900"><?php echo htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php if ($u['website'] !== null): ?>
                                            <a href="<?php echo htmlspecialchars($u['website'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="text-brand-500 hover:text-brand-700 ml-1.5"><i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i></a>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <span class="px-2 py-1 rounded-lg bg-brand-50 text-brand-700 text-xs font-medium"><?php echo htmlspecialchars($u['country'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></span>
                                    </td>
                                    <td class="px-5 py-3.5 text-slate-500 hidden md:table-cell"><?php echo htmlspecialchars($u['city'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="px-5 py-3.5 text-center">
                                        <?php if ($u['qs_rank'] !== null): ?>
                                            <span class="font-bold text-slate-700"><?php echo $u['qs_rank']; ?></span>
                                        <?php else: ?>
                                            <span class="text-slate-300">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-5 py-3.5 text-center hidden md:table-cell">
                                        <?php if ($u['usnews_rank'] !== null): ?>
                                            <span class="font-bold text-slate-700"><?php echo $u['usnews_rank']; ?></span>
                                        <?php else: ?>
                                            <span class="text-slate-300">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-5 py-3.5 text-right">
                                        <?php
                                        $editData = htmlspecialchars(json_encode([
                                            'id' => $u['id'],
                                            'name' => $u['name'],
                                            'country' => $u['country'] ?? '',
                                            'city' => $u['city'] ?? '',
                                            'qs_rank' => $u['qs_rank'] ?? '',
                                            'usnews_rank' => $u['usnews_rank'] ?? '',
                                            'website' => $u['website'] ?? '',
                                        ], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                                        ?>
                                        <button onclick='editUniversity(<?php echo $editData; ?>)' class="px-3 py-1.5 rounded-lg text-xs font-medium text-brand-600 hover:bg-brand-50 transition-colors mr-2">
                                            <i class="fa-solid fa-pen-to-square mr-1"></i>编辑
                                        </button>
                                        <form method="post" class="inline" onsubmit="return confirm('确定要删除该大学吗？关联的项目也会被删除。')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                            <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium text-red-500 hover:bg-red-50 transition-colors">
                                                <i class="fa-solid fa-trash mr-1"></i>删除
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<footer class="border-t border-slate-200/60 bg-white/50 backdrop-blur mt-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6 text-center text-xs text-slate-400">
        &copy; <?php echo date('Y'); ?> UniData Admin · 大学管理后台
    </div>
</footer>

<script>
function editUniversity(data) {
    document.getElementById('formAction').value = 'edit';
    document.getElementById('formId').value = data.id;
    document.getElementById('formName').value = data.name;
    document.getElementById('formCountry').value = data.country;
    document.getElementById('formCity').value = data.city;
    document.getElementById('formQsRank').value = data.qs_rank;
    document.getElementById('formUsnewsRank').value = data.usnews_rank;
    document.getElementById('formWebsite').value = data.website;
    document.getElementById('formTitle').textContent = '编辑大学';
    document.getElementById('formSubmit').innerHTML = '<i class="fa-solid fa-check mr-1.5"></i>保存';
    document.getElementById('cancelEditBtn').classList.remove('hidden');
    document.getElementById('universityForm').scrollIntoView({ behavior: 'smooth' });
}

function resetForm() {
    document.getElementById('formAction').value = 'add';
    document.getElementById('formId').value = '';
    document.getElementById('formName').value = '';
    document.getElementById('formCountry').value = '';
    document.getElementById('formCity').value = '';
    document.getElementById('formQsRank').value = '';
    document.getElementById('formUsnewsRank').value = '';
    document.getElementById('formWebsite').value = '';
    document.getElementById('formTitle').textContent = '添加大学';
    document.getElementById('formSubmit').innerHTML = '<i class="fa-solid fa-plus mr-1.5"></i>添加';
    document.getElementById('cancelEditBtn').classList.add('hidden');
}
</script>
</body>
</html>
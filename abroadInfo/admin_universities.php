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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-100 text-slate-900">
<nav class="bg-white/95 backdrop-blur border-b border-slate-200 sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <h1 class="text-lg md:text-xl font-bold text-sky-700">
                <i class="fa-solid fa-graduation-cap mr-2"></i>UniData Admin
            </h1>
            <a href="index.php" class="text-sm text-slate-600 hover:text-sky-700">前台</a>
            <a href="admin_universities.php" class="text-sm text-sky-700 font-semibold">大学管理</a>
            <a href="match.php" class="text-sm text-slate-600 hover:text-sky-700">智能匹配</a>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-sm text-slate-600">
                <i class="fa-solid fa-user-tie mr-1"></i><?php echo htmlspecialchars(currentUserAccount() ?? '', ENT_QUOTES, 'UTF-8'); ?>
            </span>
            <a href="logout.php" class="text-sm text-slate-500 hover:text-red-600 transition">退出</a>
        </div>
    </div>
</nav>

<main class="max-w-7xl mx-auto px-4 py-8">
    <?php if ($message !== ''): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-2xl p-4 mb-4">
            <i class="fa-solid fa-circle-check mr-1"></i><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-2xl p-4 mb-4">
            <i class="fa-solid fa-circle-exclamation mr-1"></i><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-2xl p-4 border border-slate-200 text-center">
            <p class="text-2xl font-bold text-sky-700"><?php echo count($universities); ?></p>
            <p class="text-sm text-slate-500">大学总数</p>
        </div>
        <?php foreach (array_slice($stats, 0, 3) as $s): ?>
            <div class="bg-white rounded-2xl p-4 border border-slate-200 text-center">
                <p class="text-2xl font-bold text-sky-700"><?php echo (int) $s['cnt']; ?></p>
                <p class="text-sm text-slate-500"><?php echo htmlspecialchars($s['country'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Add / Edit Form -->
        <div class="lg:col-span-1">
            <form method="post" id="universityForm" class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-4 sticky top-24">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="formId" value="">
                <h3 class="font-semibold text-base" id="formTitle">添加大学</h3>

                <div>
                    <label class="text-sm text-slate-600">大学名称 <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="formName" required placeholder="如 MIT" class="mt-1 w-full rounded-lg border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                </div>
                <div>
                    <label class="text-sm text-slate-600">国家</label>
                    <select name="country" id="formCountry" class="mt-1 w-full rounded-lg border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                        <option value="">请选择</option>
                        <?php foreach (['英国', '美国', '澳大利亚', '加拿大', '德国', '法国', '日本', '新加坡', '中国'] as $c): ?>
                            <option value="<?php echo $c; ?>"><?php echo $c; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-sm text-slate-600">城市</label>
                    <input type="text" name="city" id="formCity" placeholder="如 Cambridge, MA" class="mt-1 w-full rounded-lg border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-sm text-slate-600">QS排名</label>
                        <input type="number" name="qs_rank" id="formQsRank" placeholder="如 1" class="mt-1 w-full rounded-lg border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                    </div>
                    <div>
                        <label class="text-sm text-slate-600">US News</label>
                        <input type="number" name="usnews_rank" id="formUsnewsRank" placeholder="如 2" class="mt-1 w-full rounded-lg border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                    </div>
                </div>
                <div>
                    <label class="text-sm text-slate-600">官网</label>
                    <input type="url" name="website" id="formWebsite" placeholder="https://..." class="mt-1 w-full rounded-lg border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                </div>
                <button type="submit" class="w-full py-2 rounded-lg bg-sky-600 text-white hover:bg-sky-700 transition font-medium" id="formSubmit">
                    <i class="fa-solid fa-plus mr-1"></i>添加
                </button>
                <button type="button" id="cancelEditBtn" class="w-full py-2 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 transition hidden" onclick="resetForm()">
                    取消编辑
                </button>
            </form>
        </div>

        <!-- University List -->
        <div class="lg:col-span-3">
            <div class="overflow-x-auto bg-white border border-slate-200 rounded-2xl">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="text-left px-4 py-3 font-semibold">名称</th>
                            <th class="text-left px-4 py-3 font-semibold">国家</th>
                            <th class="text-left px-4 py-3 font-semibold">城市</th>
                            <th class="text-center px-4 py-3 font-semibold">QS</th>
                            <th class="text-center px-4 py-3 font-semibold">US News</th>
                            <th class="text-right px-4 py-3 font-semibold">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($universities === []): ?>
                            <tr>
                                <td colspan="6" class="text-center py-8 text-slate-400">暂无大学数据，请添加</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($universities as $u): ?>
                            <tr class="border-b border-slate-100 hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <span class="font-medium"><?php echo htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php if ($u['website'] !== null): ?>
                                        <a href="<?php echo htmlspecialchars($u['website'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="text-xs text-sky-600 ml-1"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-slate-600"><?php echo htmlspecialchars($u['country'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="px-4 py-3 text-slate-600"><?php echo htmlspecialchars($u['city'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="px-4 py-3 text-center"><?php echo $u['qs_rank'] ?? '-'; ?></td>
                                <td class="px-4 py-3 text-center"><?php echo $u['usnews_rank'] ?? '-'; ?></td>
                                <td class="px-4 py-3 text-right">
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
                                    <button onclick='editUniversity(<?php echo $editData; ?>)' class="text-xs text-sky-600 hover:text-sky-800 mr-2">
                                        <i class="fa-solid fa-pen-to-square"></i> 编辑
                                    </button>
                                    <form method="post" class="inline" onsubmit="return confirm('确定要删除该大学吗？')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                        <button type="submit" class="text-xs text-red-500 hover:text-red-700">
                                            <i class="fa-solid fa-trash"></i> 删除
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
</main>

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
    document.getElementById('formSubmit').innerHTML = '<i class="fa-solid fa-check mr-1"></i>保存';
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
    document.getElementById('formSubmit').innerHTML = '<i class="fa-solid fa-plus mr-1"></i>添加';
    document.getElementById('cancelEditBtn').classList.add('hidden');
}
</script>
</body>
</html>
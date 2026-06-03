<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

requireAdmin();

$pdo = db();
$message = '';
$error = '';

// Handle CRUD
$action = trim((string) ($_POST['action'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        $id = (int) ($_POST['id'] ?? 0);
        $universityId = (int) ($_POST['university_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $langReq = trim((string) ($_POST['language_requirement'] ?? ''));
        $gpaReq = $_POST['gpa_requirement'] !== '' ? (float) $_POST['gpa_requirement'] : null;
        $degreeLevel = trim((string) ($_POST['degree_level'] ?? ''));
        $deadline = $_POST['deadline_date'] !== '' ? $_POST['deadline_date'] : null;
        $pageUrl = trim((string) ($_POST['page_url'] ?? ''));

        if ($name === '' || $universityId === 0) {
            $error = '项目名称和所属大学为必填项。';
        } elseif ($action === 'add') {
            $stmt = $pdo->prepare(
                "INSERT INTO projects (university_id, name, description, language_requirement, gpa_requirement, degree_level, deadline_date, page_url) 
                 VALUES (:uid, :name, :desc, :lang, :gpa, :degree, :deadline, :url)"
            );
            $stmt->execute([
                ':uid' => $universityId,
                ':name' => $name,
                ':desc' => $description ?: null,
                ':lang' => $langReq ?: null,
                ':gpa' => $gpaReq,
                ':degree' => $degreeLevel ?: null,
                ':deadline' => $deadline,
                ':url' => $pageUrl ?: null,
            ]);
            $message = "已添加项目: {$name}";
        } else {
            $stmt = $pdo->prepare(
                "UPDATE projects SET university_id=:uid, name=:name, description=:desc, 
                 language_requirement=:lang, gpa_requirement=:gpa, degree_level=:degree, 
                 deadline_date=:deadline, page_url=:url WHERE id=:id"
            );
            $stmt->execute([
                ':id' => $id,
                ':uid' => $universityId,
                ':name' => $name,
                ':desc' => $description ?: null,
                ':lang' => $langReq ?: null,
                ':gpa' => $gpaReq,
                ':degree' => $degreeLevel ?: null,
                ':deadline' => $deadline,
                ':url' => $pageUrl ?: null,
            ]);
            $message = "已更新项目: {$name}";
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("DELETE FROM projects WHERE id = :id")->execute([':id' => $id]);
            $message = "已删除项目 ID: {$id}";
        }
    }
}

// Fetch projects with university names
$projects = $pdo->query(
    "SELECT p.*, u.name AS university_name 
     FROM projects p 
     LEFT JOIN universities u ON p.university_id = u.id 
     ORDER BY u.name, p.name"
)->fetchAll();

// Fetch universities for dropdown
$universities = $pdo->query("SELECT id, name, country FROM universities ORDER BY name")->fetchAll();

// Stats
$projectCount = (int) $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
$uniWithProjects = (int) $pdo->query("SELECT COUNT(DISTINCT university_id) FROM projects")->fetchColumn();

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>项目管理 - UniData Admin</title>
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
            <a href="admin_universities.php" class="text-sm text-slate-600 hover:text-sky-700">大学</a>
            <a href="admin_projects.php" class="text-sm text-sky-700 font-semibold">项目</a>
            <a href="match.php" class="text-sm text-slate-600 hover:text-sky-700">匹配</a>
            <a href="seed_data.sql" class="text-sm text-slate-400 hover:text-sky-600">种子数据</a>
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
            <p class="text-2xl font-bold text-sky-700"><?php echo $projectCount; ?></p>
            <p class="text-sm text-slate-500">项目总数</p>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-slate-200 text-center">
            <p class="text-2xl font-bold text-sky-700"><?php echo $uniWithProjects; ?></p>
            <p class="text-sm text-slate-500">覆盖大学</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Add / Edit Form -->
        <div class="lg:col-span-1">
            <form method="post" id="projectForm" class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-4 sticky top-24">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="formId" value="">
                <h3 class="font-semibold text-base" id="formTitle">添加项目</h3>

                <div>
                    <label class="text-sm text-slate-600">所属大学 <span class="text-red-500">*</span></label>
                    <select name="university_id" id="formUniId" required class="mt-1 w-full rounded-lg border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                        <option value="">请选择大学</option>
                        <?php foreach ($universities as $u): ?>
                            <option value="<?php echo $u['id']; ?>">
                                <?php echo htmlspecialchars($u['name'] . ' (' . ($u['country'] ?? '') . ')', ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-sm text-slate-600">项目名称 <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="formName" required placeholder="如 Computer Science (BSc)" class="mt-1 w-full rounded-lg border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                </div>
                <div>
                    <label class="text-sm text-slate-600">项目描述</label>
                    <textarea name="description" id="formDesc" rows="2" placeholder="项目简介..." class="mt-1 w-full rounded-lg border-slate-300 focus:border-sky-500 focus:ring-sky-500"></textarea>
                </div>
                <div>
                    <label class="text-sm text-slate-600">语言要求</label>
                    <input type="text" name="language_requirement" id="formLang" placeholder="如 IELTS 6.5" class="mt-1 w-full rounded-lg border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-sm text-slate-600">GPA要求</label>
                        <input type="number" name="gpa_requirement" id="formGpa" step="0.1" min="0" max="100" placeholder="如 85" class="mt-1 w-full rounded-lg border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                    </div>
                    <div>
                        <label class="text-sm text-slate-600">学位等级</label>
                        <select name="degree_level" id="formDegree" class="mt-1 w-full rounded-lg border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                            <option value="">请选择</option>
                            <option value="undergraduate">本科</option>
                            <option value="graduate">研究生</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="text-sm text-slate-600">截止日期</label>
                    <input type="date" name="deadline_date" id="formDeadline" class="mt-1 w-full rounded-lg border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                </div>
                <div>
                    <label class="text-sm text-slate-600">详情链接</label>
                    <input type="url" name="page_url" id="formUrl" placeholder="https://..." class="mt-1 w-full rounded-lg border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                </div>
                <button type="submit" class="w-full py-2 rounded-lg bg-sky-600 text-white hover:bg-sky-700 transition font-medium" id="formSubmit">
                    <i class="fa-solid fa-plus mr-1"></i>添加
                </button>
                <button type="button" id="cancelEditBtn" class="w-full py-2 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 transition hidden" onclick="resetForm()">
                    取消编辑
                </button>
            </form>
        </div>

        <!-- Project List -->
        <div class="lg:col-span-3">
            <div class="overflow-x-auto bg-white border border-slate-200 rounded-2xl">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="text-left px-4 py-3 font-semibold">项目名称</th>
                            <th class="text-left px-4 py-3 font-semibold">大学</th>
                            <th class="text-center px-4 py-3 font-semibold">学位</th>
                            <th class="text-center px-4 py-3 font-semibold">GPA</th>
                            <th class="text-center px-4 py-3 font-semibold">截止日期</th>
                            <th class="text-right px-4 py-3 font-semibold">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($projects === []): ?>
                            <tr>
                                <td colspan="6" class="text-center py-8 text-slate-400">
                                    暂无项目数据。
                                    <a href="seed_data.sql" class="text-sky-600 ml-1">导入预设种子数据 →</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($projects as $p): ?>
                            <tr class="border-b border-slate-100 hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <span class="font-medium"><?php echo htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php if ($p['page_url'] !== null): ?>
                                        <a href="<?php echo htmlspecialchars($p['page_url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="text-xs text-sky-600 ml-1"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-slate-600 text-xs"><?php echo htmlspecialchars($p['university_name'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="px-4 py-3 text-center">
                                    <?php if ($p['degree_level']): ?>
                                        <span class="text-xs px-2 py-0.5 rounded bg-purple-50 text-purple-700"><?php echo htmlspecialchars($p['degree_level'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-center"><?php echo $p['gpa_requirement'] ?? '-'; ?></td>
                                <td class="px-4 py-3 text-center text-xs"><?php echo $p['deadline_date'] ?? '-'; ?></td>
                                <td class="px-4 py-3 text-right">
                                    <?php
                                    $editData = htmlspecialchars(json_encode([
                                        'id' => $p['id'],
                                        'university_id' => $p['university_id'],
                                        'name' => $p['name'],
                                        'description' => $p['description'] ?? '',
                                        'language_requirement' => $p['language_requirement'] ?? '',
                                        'gpa_requirement' => $p['gpa_requirement'] ?? '',
                                        'degree_level' => $p['degree_level'] ?? '',
                                        'deadline_date' => $p['deadline_date'] ?? '',
                                        'page_url' => $p['page_url'] ?? '',
                                    ], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <button onclick='editProject(<?php echo $editData; ?>)' class="text-xs text-sky-600 hover:text-sky-800 mr-2">
                                        <i class="fa-solid fa-pen-to-square"></i> 编辑
                                    </button>
                                    <form method="post" class="inline" onsubmit="return confirm('确定要删除该项目吗？')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
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
function editProject(data) {
    document.getElementById('formAction').value = 'edit';
    document.getElementById('formId').value = data.id;
    document.getElementById('formUniId').value = data.university_id;
    document.getElementById('formName').value = data.name;
    document.getElementById('formDesc').value = data.description;
    document.getElementById('formLang').value = data.language_requirement;
    document.getElementById('formGpa').value = data.gpa_requirement;
    document.getElementById('formDegree').value = data.degree_level;
    document.getElementById('formDeadline').value = data.deadline_date;
    document.getElementById('formUrl').value = data.page_url;
    document.getElementById('formTitle').textContent = '编辑项目';
    document.getElementById('formSubmit').innerHTML = '<i class="fa-solid fa-check mr-1"></i>保存';
    document.getElementById('cancelEditBtn').classList.remove('hidden');
    document.getElementById('projectForm').scrollIntoView({ behavior: 'smooth' });
}

function resetForm() {
    document.getElementById('formAction').value = 'add';
    document.getElementById('formId').value = '';
    document.getElementById('formUniId').value = '';
    document.getElementById('formName').value = '';
    document.getElementById('formDesc').value = '';
    document.getElementById('formLang').value = '';
    document.getElementById('formGpa').value = '';
    document.getElementById('formDegree').value = '';
    document.getElementById('formDeadline').value = '';
    document.getElementById('formUrl').value = '';
    document.getElementById('formTitle').textContent = '添加项目';
    document.getElementById('formSubmit').innerHTML = '<i class="fa-solid fa-plus mr-1"></i>添加';
    document.getElementById('cancelEditBtn').classList.add('hidden');
}
</script>
</body>
</html>
<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

requireAdmin();

$pdo = db();
$message = '';
$error = '';

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

$projects = $pdo->query(
    "SELECT p.*, u.name AS university_name 
     FROM projects p 
     LEFT JOIN universities u ON p.university_id = u.id 
     ORDER BY u.name, p.name"
)->fetchAll();

$universities = $pdo->query("SELECT id, name, country FROM universities ORDER BY name")->fetchAll();

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
<body class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-indigo-50/30 font-sans antialiased text-slate-900">

<!-- Navbar -->
<nav class="sticky top-0 z-50 bg-white/80 backdrop-blur-xl border-b border-slate-200/60 shadow-sm shadow-slate-200/20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3.5 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-brand-600 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                <i class="fa-solid fa-list-check text-white text-lg"></i>
            </div>
            <div>
                <h1 class="text-lg font-extrabold text-slate-900 tracking-tight leading-tight">UniData <span class="text-indigo-600">Admin</span></h1>
                <p class="text-xs text-slate-400 leading-tight">项目与课程管理</p>
            </div>
        </div>

        <div class="flex items-center gap-1.5 sm:gap-3">
            <a href="index.php" class="px-3 py-2 rounded-xl text-sm font-medium text-slate-500 hover:text-brand-600 hover:bg-brand-50/50 transition-colors">
                <i class="fa-solid fa-home mr-1.5"></i><span class="hidden sm:inline">前台</span>
            </a>
            <a href="admin_universities.php" class="px-3 py-2 rounded-xl text-sm font-medium text-slate-500 hover:text-brand-600 hover:bg-brand-50/50 transition-colors">
                <i class="fa-solid fa-building-columns mr-1.5"></i><span class="hidden sm:inline">大学</span>
            </a>
            <a href="admin_projects.php" class="px-3 py-2 rounded-xl text-sm font-semibold bg-indigo-50 text-indigo-700 transition-colors">
                <i class="fa-solid fa-list-check mr-1.5"></i><span class="hidden sm:inline">项目</span>
            </a>
            <a href="match.php" class="px-3 py-2 rounded-xl text-sm font-medium text-slate-500 hover:text-brand-600 hover:bg-brand-50/50 transition-colors">
                <i class="fa-solid fa-wand-magic-sparkles mr-1.5"></i><span class="hidden sm:inline">匹配</span>
            </a>

            <div class="h-6 w-px bg-slate-200 mx-1"></div>

            <div class="flex items-center gap-2">
                <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-indigo-50">
                    <i class="fa-solid fa-user-tie text-indigo-600 text-xs"></i>
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
            <p class="text-3xl font-extrabold text-indigo-700"><?php echo $projectCount; ?></p>
            <p class="text-sm text-slate-500 font-medium mt-1">项目总数</p>
        </div>
        <div class="bg-white/80 backdrop-blur-xl rounded-2xl p-5 border border-slate-200/60 shadow-sm text-center">
            <p class="text-3xl font-extrabold text-indigo-700"><?php echo $uniWithProjects; ?></p>
            <p class="text-sm text-slate-500 font-medium mt-1">覆盖大学</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Add / Edit Form -->
        <div class="lg:col-span-1">
            <form method="post" id="projectForm" class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/60 shadow-lg shadow-slate-200/30 p-6 space-y-4 sticky top-28 animate-slide-up">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="formId" value="">
                
                <div class="flex items-center gap-2 mb-1">
                    <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center">
                        <i class="fa-solid fa-plus text-indigo-600 text-sm"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-800" id="formTitle">添加项目</h3>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1.5">所属大学 <span class="text-red-500">*</span></label>
                    <select name="university_id" id="formUniId" required class="w-full py-2.5 pl-4 pr-10 rounded-xl border-2 border-slate-200 bg-slate-50 text-slate-900 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-500/10 transition-all duration-200 outline-none text-sm appearance-none cursor-pointer" style="background-image: url('data:image/svg+xml;charset=UTF-8,%3csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%2394a3b8%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22%3e%3cpolyline points=%226 9 12 15 18 9%22%3e%3c/polyline%3e%3c/svg%3e'); background-repeat: no-repeat; background-position: right 12px center; background-size: 16px;">
                        <option value="">请选择大学</option>
                        <?php foreach ($universities as $u): ?>
                            <option value="<?php echo $u['id']; ?>">
                                <?php echo htmlspecialchars($u['name'] . ' (' . ($u['country'] ?? '') . ')', ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1.5">项目名称 <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="formName" required placeholder="如 Computer Science (BSc)" class="w-full px-4 py-2.5 rounded-xl border-2 border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-500/10 transition-all duration-200 outline-none text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1.5">项目描述</label>
                    <textarea name="description" id="formDesc" rows="2" placeholder="项目简介..." class="w-full px-4 py-2.5 rounded-xl border-2 border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-500/10 transition-all duration-200 outline-none text-sm resize-none"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1.5">语言要求</label>
                    <input type="text" name="language_requirement" id="formLang" placeholder="如 IELTS 6.5" class="w-full px-4 py-2.5 rounded-xl border-2 border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-500/10 transition-all duration-200 outline-none text-sm">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-sm font-semibold text-slate-600 mb-1.5">GPA要求</label>
                        <input type="number" name="gpa_requirement" id="formGpa" step="0.1" min="0" max="100" placeholder="如 85" class="w-full px-4 py-2.5 rounded-xl border-2 border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-500/10 transition-all duration-200 outline-none text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-600 mb-1.5">学位等级</label>
                        <select name="degree_level" id="formDegree" class="w-full py-2.5 pl-4 pr-10 rounded-xl border-2 border-slate-200 bg-slate-50 text-slate-900 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-500/10 transition-all duration-200 outline-none text-sm appearance-none cursor-pointer" style="background-image: url('data:image/svg+xml;charset=UTF-8,%3csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%2394a3b8%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22%3e%3cpolyline points=%226 9 12 15 18 9%22%3e%3c/polyline%3e%3c/svg%3e'); background-repeat: no-repeat; background-position: right 12px center; background-size: 16px;">
                            <option value="">请选择</option>
                            <option value="undergraduate">本科</option>
                            <option value="graduate">研究生</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1.5">截止日期</label>
                    <input type="date" name="deadline_date" id="formDeadline" class="w-full px-4 py-2.5 rounded-xl border-2 border-slate-200 bg-slate-50 text-slate-900 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-500/10 transition-all duration-200 outline-none text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1.5">详情链接</label>
                    <input type="url" name="page_url" id="formUrl" placeholder="https://..." class="w-full px-4 py-2.5 rounded-xl border-2 border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-500/10 transition-all duration-200 outline-none text-sm">
                </div>
                <button type="submit" class="w-full py-3 rounded-xl bg-gradient-to-r from-indigo-500 to-brand-600 text-white font-semibold text-sm shadow-lg shadow-indigo-500/20 hover:shadow-xl hover:shadow-indigo-500/30 hover:from-indigo-600 hover:to-brand-700 active:scale-[0.98] transition-all duration-200" id="formSubmit">
                    <i class="fa-solid fa-plus mr-1.5"></i>添加
                </button>
                <button type="button" id="cancelEditBtn" class="w-full py-2.5 rounded-xl border-2 border-slate-200 text-slate-600 font-medium text-sm hover:bg-slate-50 transition-colors hidden" onclick="resetForm()">
                    取消编辑
                </button>
            </form>
        </div>

        <!-- Project List -->
        <div class="lg:col-span-3">
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/60 shadow-lg shadow-slate-200/30 overflow-hidden animate-slide-up">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200">
                                <th class="text-left px-5 py-4 font-bold text-slate-700 text-xs uppercase tracking-wider">项目名称</th>
                                <th class="text-left px-5 py-4 font-bold text-slate-700 text-xs uppercase tracking-wider hidden md:table-cell">大学</th>
                                <th class="text-center px-5 py-4 font-bold text-slate-700 text-xs uppercase tracking-wider">学位</th>
                                <th class="text-center px-5 py-4 font-bold text-slate-700 text-xs uppercase tracking-wider hidden sm:table-cell">GPA</th>
                                <th class="text-center px-5 py-4 font-bold text-slate-700 text-xs uppercase tracking-wider hidden lg:table-cell">截止</th>
                                <th class="text-right px-5 py-4 font-bold text-slate-700 text-xs uppercase tracking-wider">操作</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if ($projects === []): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-12 text-slate-400">
                                        <div class="flex flex-col items-center gap-2">
                                            <i class="fa-solid fa-list-check text-3xl text-slate-300"></i>
                                            <p>暂无项目数据，请在左侧添加</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($projects as $p): ?>
                                <tr class="hover:bg-indigo-50/20 transition-colors">
                                    <td class="px-5 py-3.5">
                                        <span class="font-semibold text-slate-900"><?php echo htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php if ($p['page_url'] !== null): ?>
                                            <a href="<?php echo htmlspecialchars($p['page_url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="text-brand-500 hover:text-brand-700 ml-1.5"><i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i></a>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-5 py-3.5 text-slate-500 text-xs hidden md:table-cell"><?php echo htmlspecialchars($p['university_name'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="px-5 py-3.5 text-center">
                                        <?php if ($p['degree_level']): ?>
                                            <span class="px-2 py-1 rounded-lg bg-purple-50 text-purple-700 text-xs font-medium">
                                                <?php echo htmlspecialchars($p['degree_level'] === 'undergraduate' ? '本科' : '研究生', ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-slate-300">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-5 py-3.5 text-center hidden sm:table-cell">
                                        <?php if ($p['gpa_requirement'] !== null): ?>
                                            <span class="font-bold text-slate-700"><?php echo $p['gpa_requirement']; ?></span>
                                        <?php else: ?>
                                            <span class="text-slate-300">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-5 py-3.5 text-center text-xs hidden lg:table-cell"><?php echo $p['deadline_date'] ?? '-'; ?></td>
                                    <td class="px-5 py-3.5 text-right">
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
                                        <button onclick='editProject(<?php echo $editData; ?>)' class="px-3 py-1.5 rounded-lg text-xs font-medium text-brand-600 hover:bg-brand-50 transition-colors mr-2">
                                            <i class="fa-solid fa-pen-to-square mr-1"></i>编辑
                                        </button>
                                        <form method="post" class="inline" onsubmit="return confirm('确定要删除该项目吗？')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
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
        &copy; <?php echo date('Y'); ?> UniData Admin · 项目管理后台
    </div>
</footer>

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
    document.getElementById('formSubmit').innerHTML = '<i class="fa-solid fa-check mr-1.5"></i>保存';
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
    document.getElementById('formSubmit').innerHTML = '<i class="fa-solid fa-plus mr-1.5"></i>添加';
    document.getElementById('cancelEditBtn').classList.add('hidden');
}
</script>
</body>
</html>
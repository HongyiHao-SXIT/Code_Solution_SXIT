<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/entity/User.php';

startSession();

$error = '';
$results = [];
$hasSearched = false;

$gpa = '';
$ielts = '';
$toefl = '';
$preferredCountry = '';
$degreeLevel = '';
$budget = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hasSearched = true;
    $gpa = trim((string) ($_POST['gpa'] ?? ''));
    $ielts = trim((string) ($_POST['ielts'] ?? ''));
    $toefl = trim((string) ($_POST['toefl'] ?? ''));
    $preferredCountry = trim((string) ($_POST['preferred_country'] ?? ''));
    $degreeLevel = trim((string) ($_POST['degree_level'] ?? ''));
    $budget = trim((string) ($_POST['budget'] ?? ''));

    if ($gpa === '') {
        $error = '请输入GPA成绩。';
    } else {
        $gpaVal = (float) $gpa;

        // Build program list from DB
        $pdo = db();
        $sql = "
            SELECT p.id, p.name AS program_name, p.degree_level, p.gpa_requirement,
                   p.language_requirement, p.deadline_date, p.page_url,
                   u.name AS university_name, u.country, u.qs_rank, u.usnews_rank, u.website
            FROM projects p
            LEFT JOIN universities u ON p.university_id = u.id
        ";
        $dbPrograms = $pdo->query($sql)->fetchAll();

        if (count($dbPrograms) > 0) {
            // Simple PHP matching as fallback when Python is not available
            foreach ($dbPrograms as $prog) {
                $score = 0;
                $reasons = [];

                // GPA match (40%)
                if ($prog['gpa_requirement'] !== null) {
                    $minGpa = (float) $prog['gpa_requirement'];
                    if ($gpaVal >= $minGpa) {
                        $score += 40;
                        $reasons[] = "GPA达到要求";
                    } elseif ($gpaVal >= $minGpa - 5) {
                        $score += 20;
                        $reasons[] = "GPA接近要求（差" . round($minGpa - $gpaVal, 1) . "分）";
                    } else {
                        $score += 5;
                        $reasons[] = "GPA未达到要求";
                    }
                } else {
                    $score += 30;
                    $reasons[] = "无限定GPA要求";
                }

                // Language match (25%)
                $langReq = $prog['language_requirement'] ?? '';
                $ieltsVal = $ielts !== '' ? (float) $ielts : null;
                $toeflVal = $toefl !== '' ? (float) $toefl : null;

                if ($ieltsVal !== null && stripos($langReq, 'IELTS') !== false) {
                    preg_match('/(\d+\.?\d*)/', $langReq, $m);
                    $minLang = isset($m[1]) ? (float) $m[1] : 6.0;
                    if ($ieltsVal >= $minLang) {
                        $score += 25;
                        $reasons[] = "IELTS {$ieltsVal} ≥ {$minLang}";
                    } else {
                        $score += 10;
                        $reasons[] = "IELTS未达标（需要 {$minLang}）";
                    }
                } elseif ($toeflVal !== null && stripos($langReq, 'TOEFL') !== false) {
                    preg_match('/(\d+)/', $langReq, $m);
                    $minLang = isset($m[1]) ? (float) $m[1] : 90;
                    if ($toeflVal >= $minLang) {
                        $score += 25;
                        $reasons[] = "TOEFL {$toeflVal} ≥ {$minLang}";
                    } else {
                        $score += 10;
                        $reasons[] = "TOEFL未达标（需要 {$minLang}）";
                    }
                } else {
                    $score += 15;
                    $reasons[] = "语言要求未明确";
                }

                // Country match (15%)
                if ($preferredCountry !== '' && $prog['country'] === $preferredCountry) {
                    $score += 15;
                    $reasons[] = "目标国家匹配";
                } elseif ($preferredCountry !== '') {
                    $score += 3;
                    $reasons[] = "非目标国家";
                } else {
                    $score += 10;
                }

                // Ranking bonus (10%)
                $qsRank = $prog['qs_rank'] !== null ? (int) $prog['qs_rank'] : null;
                if ($qsRank !== null) {
                    if ($qsRank <= 10) {
                        $score += 10;
                        $reasons[] = "QS排名前10";
                    } elseif ($qsRank <= 50) {
                        $score += 7;
                        $reasons[] = "QS排名前50";
                    } elseif ($qsRank <= 100) {
                        $score += 5;
                        $reasons[] = "QS排名前100";
                    } else {
                        $score += 2;
                    }
                }

                // Degree level (10%)
                if ($degreeLevel !== '' && $prog['degree_level'] !== null) {
                    if (stripos($prog['degree_level'], $degreeLevel) !== false) {
                        $score += 10;
                        $reasons[] = "学位等级匹配";
                    } else {
                        $score += 3;
                    }
                } else {
                    $score += 5;
                }

                $results[] = [
                    'program_name' => $prog['program_name'],
                    'university_name' => $prog['university_name'] ?? '未知大学',
                    'country' => $prog['country'] ?? '未知',
                    'degree_level' => $prog['degree_level'] ?? '',
                    'gpa_requirement' => $prog['gpa_requirement'],
                    'language_requirement' => $prog['language_requirement'] ?? '',
                    'qs_rank' => $prog['qs_rank'],
                    'deadline_date' => $prog['deadline_date'],
                    'page_url' => $prog['page_url'] ?? ($prog['website'] ?? ''),
                    'match_score' => round($score, 1),
                    'match_reasons' => $reasons,
                ];
            }

            // Sort by score descending
            usort($results, static fn($a, $b) => $b['match_score'] <=> $a['match_score']);
            $results = array_slice($results, 0, 20);
        }
    }
}

// Get countries list for dropdown
$pdo = db();
$countryList = $pdo->query("SELECT DISTINCT country FROM universities WHERE country IS NOT NULL AND country != '' ORDER BY country")->fetchAll(PDO::FETCH_COLUMN);

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>智能匹配 - UniData</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-100 text-slate-900">
<nav class="bg-white/95 backdrop-blur border-b border-slate-200 sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <h1 class="text-lg md:text-xl font-bold text-sky-700">
                <i class="fa-solid fa-graduation-cap mr-2"></i>UniData
            </h1>
            <a href="index.php" class="text-sm text-slate-600 hover:text-sky-700">招生信息</a>
            <a href="match.php" class="text-sm text-sky-700 font-semibold">智能匹配</a>
        </div>
        <div class="flex items-center gap-3">
            <?php if (isLoggedIn()): ?>
                <span class="text-sm text-slate-600">
                    <i class="fa-solid fa-user mr-1"></i><?php echo htmlspecialchars(currentUserAccount() ?? '', ENT_QUOTES, 'UTF-8'); ?>
                </span>
                <a href="logout.php" class="text-sm text-slate-500 hover:text-red-600 transition">
                    <i class="fa-solid fa-right-from-bracket mr-1"></i>退出
                </a>
            <?php else: ?>
                <a href="login.php" class="text-sm text-sky-600 hover:text-sky-800">登录</a>
                <a href="register.php" class="text-sm px-3 py-1 rounded-lg bg-sky-600 text-white hover:bg-sky-700">注册</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="max-w-7xl mx-auto px-4 py-8">
    <div class="mb-6">
        <h2 class="text-2xl font-bold">
            <i class="fa-solid fa-wand-magic-sparkles text-sky-600 mr-2"></i>智能匹配
        </h2>
        <p class="text-slate-500 mt-1">输入你的学术背景，系统将为你推荐最匹配的大学和项目</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Input Form -->
        <div class="lg:col-span-1">
            <form method="post" class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-4 sticky top-24">
                <h3 class="font-semibold text-base">你的背景信息</h3>

                <div>
                    <label class="text-sm text-slate-600">GPA / 均分 <span class="text-red-500">*</span></label>
                    <input
                        type="number"
                        name="gpa"
                        step="0.1"
                        min="0"
                        max="100"
                        value="<?php echo htmlspecialchars($gpa, ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="如 85（百分制）"
                        required
                        class="mt-1 w-full rounded-lg border-slate-300 focus:border-sky-500 focus:ring-sky-500"
                    >
                    <span class="text-xs text-slate-400">百分制，如 85 分</span>
                </div>

                <div>
                    <label class="text-sm text-slate-600">IELTS 成绩</label>
                    <input
                        type="number"
                        name="ielts"
                        step="0.5"
                        min="0"
                        max="9"
                        value="<?php echo htmlspecialchars($ielts, ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="如 6.5"
                        class="mt-1 w-full rounded-lg border-slate-300 focus:border-sky-500 focus:ring-sky-500"
                    >
                </div>

                <div>
                    <label class="text-sm text-slate-600">TOEFL iBT 成绩</label>
                    <input
                        type="number"
                        name="toefl"
                        step="1"
                        min="0"
                        max="120"
                        value="<?php echo htmlspecialchars($toefl, ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="如 90"
                        class="mt-1 w-full rounded-lg border-slate-300 focus:border-sky-500 focus:ring-sky-500"
                    >
                </div>

                <div>
                    <label class="text-sm text-slate-600">意向国家</label>
                    <select name="preferred_country" class="mt-1 w-full rounded-lg border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                        <option value="">不限</option>
                        <?php foreach (['英国', '美国', '澳大利亚', '加拿大', '德国', '法国', '日本', '新加坡', '中国'] as $c): ?>
                            <option value="<?php echo $c; ?>" <?php echo $preferredCountry === $c ? 'selected' : ''; ?>>
                                <?php echo $c; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="text-sm text-slate-600">学位等级</label>
                    <select name="degree_level" class="mt-1 w-full rounded-lg border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                        <option value="">不限</option>
                        <option value="undergraduate" <?php echo $degreeLevel === 'undergraduate' ? 'selected' : ''; ?>>本科 (Undergraduate)</option>
                        <option value="graduate" <?php echo $degreeLevel === 'graduate' ? 'selected' : ''; ?>>研究生 (Graduate)</option>
                    </select>
                </div>

                <div>
                    <label class="text-sm text-slate-600">年度预算 (USD)</label>
                    <input
                        type="number"
                        name="budget"
                        step="1000"
                        min="0"
                        value="<?php echo htmlspecialchars($budget, ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="如 40000"
                        class="mt-1 w-full rounded-lg border-slate-300 focus:border-sky-500 focus:ring-sky-500"
                    >
                </div>

                <button class="w-full py-2.5 rounded-lg bg-sky-600 text-white hover:bg-sky-700 transition font-medium">
                    <i class="fa-solid fa-magnifying-glass mr-1"></i>开始匹配
                </button>
            </form>
        </div>

        <!-- Results -->
        <div class="lg:col-span-2">
            <?php if ($error !== ''): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 rounded-2xl p-4 mb-4">
                    <i class="fa-solid fa-circle-exclamation mr-1"></i><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <?php if ($hasSearched && $results === []): ?>
                <div class="bg-amber-50 border border-amber-200 rounded-2xl p-8 text-center text-amber-700">
                    <p class="font-semibold mb-2">暂无匹配结果</p>
                    <p class="text-sm">数据库中的项目数据不足，请先添加大学和项目数据。</p>
                    <?php if (isAdmin()): ?>
                        <a href="admin_universities.php" class="inline-block mt-3 px-4 py-2 bg-amber-600 text-white rounded-lg text-sm hover:bg-amber-700">
                            前往管理页面添加数据
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($results !== []): ?>
                <p class="text-sm text-slate-500 mb-4">找到 <?php echo count($results); ?> 个匹配结果</p>
                <?php foreach ($results as $i => $r): ?>
                    <?php
                    $scoreClass = $r['match_score'] >= 80 ? 'text-emerald-600' : ($r['match_score'] >= 55 ? 'text-amber-600' : 'text-slate-500');
                    $tierLabel = $r['match_score'] >= 80 ? '冲刺' : ($r['match_score'] >= 55 ? '匹配' : '保底');
                    $tierColor = $r['match_score'] >= 80 ? 'bg-emerald-100 text-emerald-700' : ($r['match_score'] >= 55 ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600');
                    ?>
                    <article class="bg-white border border-slate-200 rounded-2xl p-5 mb-4 shadow-sm hover:shadow-md transition">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <h3 class="text-lg font-semibold text-slate-900"><?php echo htmlspecialchars($r['program_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <span class="text-xs px-2 py-0.5 rounded-full <?php echo $tierColor; ?>"><?php echo $tierLabel; ?></span>
                                </div>
                                <p class="text-sm text-slate-500">
                                    <?php echo htmlspecialchars($r['university_name'], ENT_QUOTES, 'UTF-8'); ?>
                                    · <?php echo htmlspecialchars($r['country'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if ($r['qs_rank'] !== null): ?>
                                        · <span class="text-xs text-slate-400">QS #<?php echo $r['qs_rank']; ?></span>
                                    <?php endif; ?>
                                </p>

                                <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                    <?php if ($r['gpa_requirement'] !== null): ?>
                                        <span class="px-2 py-1 rounded bg-blue-50 text-blue-700">GPA ≥ <?php echo $r['gpa_requirement']; ?></span>
                                    <?php endif; ?>
                                    <?php if ($r['language_requirement'] !== ''): ?>
                                        <span class="px-2 py-1 rounded bg-indigo-50 text-indigo-700"><?php echo htmlspecialchars($r['language_requirement'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endif; ?>
                                    <?php if ($r['degree_level'] !== ''): ?>
                                        <span class="px-2 py-1 rounded bg-purple-50 text-purple-700"><?php echo htmlspecialchars($r['degree_level'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="mt-3 flex flex-wrap gap-1">
                                    <?php foreach ($r['match_reasons'] as $reason): ?>
                                        <span class="text-xs text-slate-500 bg-slate-50 px-2 py-0.5 rounded"><?php echo htmlspecialchars($reason, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="text-right shrink-0">
                                <p class="text-2xl font-bold <?php echo $scoreClass; ?>"><?php echo $r['match_score']; ?></p>
                                <p class="text-xs text-slate-400">匹配分</p>
                                <?php if ($r['deadline_date'] !== null): ?>
                                    <p class="text-xs text-slate-500 mt-2">截止: <?php echo htmlspecialchars($r['deadline_date'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <?php endif; ?>
                                <?php if ($r['page_url'] !== ''): ?>
                                    <a href="<?php echo htmlspecialchars($r['page_url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="inline-block mt-2 text-xs text-sky-600 hover:text-sky-800">
                                        查看详情 <i class="fa-solid fa-arrow-up-right-from-square ml-1"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!$hasSearched): ?>
                <div class="bg-white border border-slate-200 rounded-2xl p-8 text-center text-slate-400">
                    <i class="fa-solid fa-arrow-left text-4xl mb-3 block"></i>
                    <p>请在左侧填写你的背景信息，然后点击"开始匹配"</p>
                    <p class="text-sm mt-2">系统将根据 GPA、语言成绩、意向国家等因素为你推荐最合适的大学</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>
</body>
</html>
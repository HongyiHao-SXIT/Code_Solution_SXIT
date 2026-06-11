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
            foreach ($dbPrograms as $prog) {
                $score = 0;
                $reasons = [];

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

                if ($preferredCountry !== '' && $prog['country'] === $preferredCountry) {
                    $score += 15;
                    $reasons[] = "目标国家匹配";
                } elseif ($preferredCountry !== '') {
                    $score += 3;
                    $reasons[] = "非目标国家";
                } else {
                    $score += 10;
                }

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

            usort($results, static fn($a, $b) => $b['match_score'] <=> $a['match_score']);
            $results = array_slice($results, 0, 20);
        }
    }
}

$pdo = db();
$countryList = $pdo->query("SELECT DISTINCT country FROM universities WHERE country IS NOT NULL AND country != '' ORDER BY country")->fetchAll(PDO::FETCH_COLUMN);

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>智能匹配 - UniData 留学数据平台</title>
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
                        'pulse-slow': 'pulse 3s ease-in-out infinite',
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
<body class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-brand-50/30 font-sans antialiased text-slate-900">

<!-- Modern Navbar -->
<nav class="sticky top-0 z-50 bg-white/80 backdrop-blur-xl border-b border-slate-200/60 shadow-sm shadow-slate-200/20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3.5 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-gradient-to-br from-brand-600 to-blue-600 flex items-center justify-center shadow-lg shadow-brand-500/20">
                <i class="fa-solid fa-graduation-cap text-white text-lg"></i>
            </div>
            <div class="hidden sm:block">
                <h1 class="text-lg font-extrabold text-slate-900 tracking-tight leading-tight">UniData</h1>
                <p class="text-xs text-slate-400 leading-tight">全球招生要求平台</p>
            </div>
        </div>

        <div class="flex items-center gap-1.5 sm:gap-3">
            <a href="index.php" class="px-3 py-2 rounded-xl text-sm font-medium text-slate-500 hover:text-brand-600 hover:bg-brand-50/50 transition-colors">
                <i class="fa-solid fa-magnifying-glass mr-1.5"></i><span class="hidden sm:inline">招生信息</span>
            </a>
            <a href="match.php" class="px-3 py-2 rounded-xl text-sm font-semibold bg-brand-50 text-brand-700 transition-colors">
                <i class="fa-solid fa-wand-magic-sparkles mr-1.5"></i><span class="hidden sm:inline">智能匹配</span>
            </a>

            <div class="h-6 w-px bg-slate-200 mx-1"></div>

            <?php if (isLoggedIn()): ?>
                <div class="flex items-center gap-2">
                    <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-50">
                        <div class="w-7 h-7 rounded-full bg-gradient-to-br from-brand-500 to-blue-500 flex items-center justify-center">
                            <span class="text-white text-xs font-bold"><?php echo mb_substr(currentUserAccount() ?? 'U', 0, 1); ?></span>
                        </div>
                        <span class="text-sm font-medium text-slate-700"><?php echo htmlspecialchars(currentUserAccount() ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <?php if (isAdmin()): ?>
                        <a href="admin_universities.php" class="px-2.5 py-1.5 rounded-xl text-xs font-medium bg-amber-50 text-amber-700 hover:bg-amber-100 transition-colors">
                            <i class="fa-solid fa-gear mr-1"></i>管理
                        </a>
                    <?php endif; ?>
                    <a href="logout.php" class="p-2 rounded-xl text-slate-400 hover:text-red-500 hover:bg-red-50 transition-colors" title="退出登录">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </a>
                </div>
            <?php else: ?>
                <div class="flex items-center gap-2">
                    <a href="login.php" class="px-4 py-2 rounded-xl text-sm font-medium text-slate-600 hover:text-brand-600 hover:bg-brand-50 transition-colors">
                        登录
                    </a>
                    <a href="register.php" class="px-4 py-2 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-brand-600 to-blue-600 hover:from-brand-700 hover:to-blue-700 shadow-md shadow-brand-500/20 transition-all">
                        注册
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
    <!-- Hero -->
    <div class="mb-8 animate-fade-in">
        <div class="bg-gradient-to-r from-amber-500 via-orange-500 to-brand-600 rounded-3xl p-8 sm:p-10 text-white shadow-2xl shadow-amber-500/20 relative overflow-hidden">
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 right-0 w-64 h-64 bg-white rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 left-0 w-48 h-48 bg-white rounded-full blur-3xl"></div>
            </div>
            <div class="relative">
                <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight mb-3">
                    <i class="fa-solid fa-wand-magic-sparkles mr-3"></i>智能匹配
                </h1>
                <p class="text-amber-100 text-lg max-w-2xl">
                    输入你的学术背景信息，系统将根据GPA、语言成绩、意向国家等因素，智能推荐最匹配的大学和项目。
                </p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Input Form -->
        <div class="lg:col-span-1">
            <form method="post" class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/60 shadow-lg shadow-slate-200/30 p-6 space-y-5 sticky top-28 animate-slide-up">
                <div class="flex items-center gap-2 mb-1">
                    <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center">
                        <i class="fa-solid fa-user-graduate text-amber-600 text-sm"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-800">你的背景信息</h3>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2">
                        GPA / 均分 <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <i class="fa-solid fa-chart-simple text-slate-400 text-sm"></i>
                        </div>
                        <input
                            type="number"
                            name="gpa"
                            step="0.1"
                            min="0"
                            max="100"
                            value="<?php echo htmlspecialchars($gpa, ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="如 85（百分制）"
                            required
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border-2 border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-500/10 transition-all duration-200 outline-none text-sm"
                        >
                    </div>
                    <p class="text-xs text-slate-400 mt-1 ml-1">百分制成绩，例如 85 分</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2">IELTS 成绩</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <span class="text-slate-400 text-xs font-bold">IELTS</span>
                        </div>
                        <input
                            type="number"
                            name="ielts"
                            step="0.5"
                            min="0"
                            max="9"
                            value="<?php echo htmlspecialchars($ielts, ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="如 6.5"
                            class="w-full pl-16 pr-4 py-2.5 rounded-xl border-2 border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-500/10 transition-all duration-200 outline-none text-sm"
                        >
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2">TOEFL iBT 成绩</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <span class="text-slate-400 text-xs font-bold">TOEFL</span>
                        </div>
                        <input
                            type="number"
                            name="toefl"
                            step="1"
                            min="0"
                            max="120"
                            value="<?php echo htmlspecialchars($toefl, ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="如 90"
                            class="w-full pl-16 pr-4 py-2.5 rounded-xl border-2 border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-500/10 transition-all duration-200 outline-none text-sm"
                        >
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2">意向国家</label>
                    <select name="preferred_country" class="w-full py-2.5 pl-4 pr-10 rounded-xl border-2 border-slate-200 bg-slate-50 text-slate-900 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-500/10 transition-all duration-200 outline-none text-sm appearance-none cursor-pointer" style="background-image: url('data:image/svg+xml;charset=UTF-8,%3csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%2394a3b8%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22%3e%3cpolyline points=%226 9 12 15 18 9%22%3e%3c/polyline%3e%3c/svg%3e'); background-repeat: no-repeat; background-position: right 12px center; background-size: 16px;">
                        <option value="">不限</option>
                        <?php foreach (['英国', '美国', '澳大利亚', '加拿大', '德国', '法国', '日本', '新加坡', '中国'] as $c): ?>
                            <option value="<?php echo $c; ?>" <?php echo $preferredCountry === $c ? 'selected' : ''; ?>>
                                <?php echo $c; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2">学位等级</label>
                    <select name="degree_level" class="w-full py-2.5 pl-4 pr-10 rounded-xl border-2 border-slate-200 bg-slate-50 text-slate-900 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-500/10 transition-all duration-200 outline-none text-sm appearance-none cursor-pointer" style="background-image: url('data:image/svg+xml;charset=UTF-8,%3csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%2394a3b8%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22%3e%3cpolyline points=%226 9 12 15 18 9%22%3e%3c/polyline%3e%3c/svg%3e'); background-repeat: no-repeat; background-position: right 12px center; background-size: 16px;">
                        <option value="">不限</option>
                        <option value="undergraduate" <?php echo $degreeLevel === 'undergraduate' ? 'selected' : ''; ?>>本科 (Undergraduate)</option>
                        <option value="graduate" <?php echo $degreeLevel === 'graduate' ? 'selected' : ''; ?>>研究生 (Graduate)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2">年度预算 (USD)</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <i class="fa-solid fa-dollar-sign text-slate-400 text-sm"></i>
                        </div>
                        <input
                            type="number"
                            name="budget"
                            step="1000"
                            min="0"
                            value="<?php echo htmlspecialchars($budget, ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="如 40000"
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border-2 border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-500/10 transition-all duration-200 outline-none text-sm"
                        >
                    </div>
                </div>

                <button class="w-full py-3 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 text-white font-bold text-base shadow-lg shadow-amber-500/20 hover:shadow-xl hover:shadow-amber-500/30 hover:from-amber-600 hover:to-orange-600 active:scale-[0.98] transition-all duration-200">
                    <i class="fa-solid fa-magnifying-glass mr-2"></i>开始匹配
                </button>
            </form>
        </div>

        <!-- Results -->
        <div class="lg:col-span-2">
            <?php if ($error !== ''): ?>
                <div class="flex items-start gap-3 bg-red-50 border border-red-200 rounded-2xl p-4 mb-5 animate-fade-in">
                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                        <i class="fa-solid fa-circle-exclamation text-red-500 text-xs"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-red-800">匹配失败</p>
                        <p class="text-red-600 text-sm mt-0.5"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($hasSearched && $results === []): ?>
                <div class="bg-amber-50/80 backdrop-blur rounded-3xl border border-amber-200 p-10 text-center animate-fade-in">
                    <div class="w-20 h-20 mx-auto mb-5 rounded-2xl bg-amber-100 flex items-center justify-center">
                        <i class="fa-solid fa-inbox text-amber-500 text-3xl"></i>
                    </div>
                    <p class="text-amber-800 font-bold text-xl mb-2">暂无匹配结果</p>
                    <p class="text-amber-700 mb-4 text-sm">数据库中的项目数据不足，请先添加大学和项目数据。</p>
                    <?php if (isAdmin()): ?>
                        <a href="admin_universities.php" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-amber-600 text-white font-semibold text-sm hover:bg-amber-700 shadow-lg shadow-amber-500/20 transition-all">
                            <i class="fa-solid fa-gear"></i> 前往管理页面
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($results !== []): ?>
                <p class="text-sm font-semibold text-slate-600 mb-4 animate-fade-in">
                    <i class="fa-solid fa-list-check mr-1.5 text-brand-500"></i>找到 <?php echo count($results); ?> 个匹配结果
                </p>
                <?php foreach ($results as $i => $r): ?>
                    <?php
                    $scoreClass = $r['match_score'] >= 80 ? 'text-emerald-600' : ($r['match_score'] >= 55 ? 'text-amber-600' : 'text-slate-500');
                    $tierLabel = $r['match_score'] >= 80 ? '冲刺' : ($r['match_score'] >= 55 ? '匹配' : '保底');
                    $tierColor = $r['match_score'] >= 80 ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : ($r['match_score'] >= 55 ? 'bg-amber-100 text-amber-700 border-amber-200' : 'bg-slate-100 text-slate-600 border-slate-200');
                    $scoreBar = $r['match_score'] >= 80 ? 'from-emerald-500 to-emerald-400' : ($r['match_score'] >= 55 ? 'from-amber-500 to-amber-400' : 'from-slate-400 to-slate-300');
                    $scoreBg = $r['match_score'] >= 80 ? 'bg-emerald-50' : ($r['match_score'] >= 55 ? 'bg-amber-50' : 'bg-slate-50');
                    ?>
                    <article class="bg-white/80 backdrop-blur-xl rounded-2xl border border-slate-200/60 p-5 sm:p-6 mb-4 shadow-sm hover:shadow-lg hover:border-brand-200/50 transition-all duration-300 group animate-slide-up">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 mb-1 flex-wrap">
                                    <h3 class="text-lg font-bold text-slate-900 group-hover:text-brand-700 transition-colors">
                                        <?php echo htmlspecialchars($r['program_name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </h3>
                                    <span class="text-xs px-2.5 py-1 rounded-full border font-semibold <?php echo $tierColor; ?>">
                                        <?php echo $tierLabel; ?>
                                    </span>
                                </div>
                                <p class="text-sm text-slate-500 flex items-center flex-wrap gap-1.5">
                                    <i class="fa-solid fa-building-columns text-xs"></i>
                                    <?php echo htmlspecialchars($r['university_name'], ENT_QUOTES, 'UTF-8'); ?>
                                    <span class="text-slate-300">·</span>
                                    <?php echo htmlspecialchars($r['country'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if ($r['qs_rank'] !== null): ?>
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-brand-50 text-brand-700 text-xs font-semibold">
                                            QS #<?php echo $r['qs_rank']; ?>
                                        </span>
                                    <?php endif; ?>
                                </p>

                                <div class="mt-3 flex flex-wrap gap-1.5 text-xs">
                                    <?php if ($r['gpa_requirement'] !== null): ?>
                                        <span class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 font-medium">
                                            <i class="fa-solid fa-chart-line mr-1"></i>GPA ≥ <?php echo $r['gpa_requirement']; ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($r['language_requirement'] !== ''): ?>
                                        <span class="px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 font-medium">
                                            <i class="fa-solid fa-language mr-1"></i><?php echo htmlspecialchars($r['language_requirement'], ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($r['degree_level'] !== ''): ?>
                                        <span class="px-2.5 py-1 rounded-lg bg-purple-50 text-purple-700 font-medium">
                                            <i class="fa-solid fa-graduation-cap mr-1"></i><?php echo htmlspecialchars($r['degree_level'], ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="mt-3 flex flex-wrap gap-1">
                                    <?php foreach ($r['match_reasons'] as $reason): ?>
                                        <span class="text-xs text-slate-500 bg-slate-50 px-2 py-0.5 rounded-md"><?php echo htmlspecialchars($reason, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="text-center shrink-0 <?php echo $scoreBg; ?> rounded-2xl p-4 min-w-[90px]">
                                <p class="text-3xl font-extrabold <?php echo $scoreClass; ?>"><?php echo $r['match_score']; ?></p>
                                <p class="text-xs text-slate-400 font-medium mt-1">匹配分</p>
                                <div class="mt-2 w-full h-1.5 rounded-full bg-slate-200 overflow-hidden">
                                    <div class="h-full rounded-full bg-gradient-to-r <?php echo $scoreBar; ?>" style="width: <?php echo min(100, $r['match_score']); ?>%"></div>
                                </div>
                                <?php if ($r['deadline_date'] !== null): ?>
                                    <p class="text-xs text-slate-500 mt-3">
                                        <i class="fa-solid fa-calendar mr-1"></i><?php echo htmlspecialchars($r['deadline_date'], ENT_QUOTES, 'UTF-8'); ?>
                                    </p>
                                <?php endif; ?>
                                <?php if ($r['page_url'] !== ''): ?>
                                    <a href="<?php echo htmlspecialchars($r['page_url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-1 mt-2 text-xs font-semibold text-brand-600 hover:text-brand-700 transition-colors">
                                        详情 <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!$hasSearched): ?>
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/60 p-12 text-center animate-fade-in">
                    <div class="w-24 h-24 mx-auto mb-6 rounded-3xl bg-gradient-to-br from-brand-50 to-blue-50 flex items-center justify-center">
                        <i class="fa-solid fa-arrow-left text-brand-400 text-4xl"></i>
                    </div>
                    <p class="text-slate-700 font-bold text-xl mb-2">开始智能匹配</p>
                    <p class="text-slate-400 text-sm max-w-md mx-auto">
                        请在左侧填写你的学术背景信息（GPA、语言成绩等），然后点击"开始匹配"按钮，<br>系统将为你推荐最合适的大学项目。
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<footer class="border-t border-slate-200/60 bg-white/50 backdrop-blur mt-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6 text-center text-xs text-slate-400">
        &copy; <?php echo date('Y'); ?> UniData · 全球留学数据平台 · 匹配结果仅供参考
    </div>
</footer>
</body>
</html>
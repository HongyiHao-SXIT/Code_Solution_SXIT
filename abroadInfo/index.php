<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function detectLanguageTags(string $text): array
{
    $tags = [];
    $checks = [
        'IELTS' => '/\bIELTS\b|雅思/i',
        'TOEFL' => '/\bTOEFL\b|托福/i',
        'SAT'   => '/\bSAT\b/i',
        'ACT'   => '/\bACT\b/i',
        'GRE'   => '/\bGRE\b/i',
        'GMAT'  => '/\bGMAT\b/i',
    ];

    foreach ($checks as $label => $pattern) {
        if (preg_match($pattern, $text)) {
            $tags[] = $label;
        }
    }

    return $tags;
}

function statusText(?\DateTimeImmutable $deadline): array
{
    if ($deadline === null) {
        return ['text' => '待确认', 'class' => 'text-slate-500', 'bg' => 'bg-slate-50 text-slate-600'];
    }

    $today = new DateTimeImmutable('today');
    $days  = (int) $today->diff($deadline)->format('%r%a');

    if ($days < 0) {
        return ['text' => '已截止', 'class' => 'text-gray-500', 'bg' => 'bg-gray-100 text-gray-600'];
    }
    if ($days <= 20) {
        return ['text' => '即将截止', 'class' => 'text-amber-600', 'bg' => 'bg-amber-50 text-amber-700'];
    }

    return ['text' => '开放中', 'class' => 'text-emerald-600', 'bg' => 'bg-emerald-50 text-emerald-700'];
}

// ---------- Request parameters ----------
$q            = trim((string) ($_GET['q'] ?? ''));
$countryFilter = trim((string) ($_GET['country'] ?? '全部'));
$examFilter   = trim((string) ($_GET['exam'] ?? '全部'));
$sort         = trim((string) ($_GET['sort'] ?? 'updated'));
$page         = max(1, (int) ($_GET['page'] ?? 1));
$perPage      = 8;

// ---------- Build query ----------
$pdo = db();

$where  = [];
$params = [];

if ($q !== '') {
    $where[]  = "(university_home LIKE :q1 OR page_title LIKE :q2 OR requirement_snippet LIKE :q3 OR page_url LIKE :q4)";
    $likeQ    = '%' . $q . '%';
    $params[':q1'] = $likeQ;
    $params[':q2'] = $likeQ;
    $params[':q3'] = $likeQ;
    $params[':q4'] = $likeQ;
}

if ($countryFilter !== '全部') {
    $countryMap = [
        '英国'     => '.uk',
        '美国'     => '.edu',
        '澳大利亚' => '.edu.au',
        '加拿大'   => '.ca',
        '德国'     => '.de',
        '法国'     => '.fr',
        '日本'     => '.jp',
        '新加坡'   => '.sg',
        '中国'     => '.cn',
    ];
    if (isset($countryMap[$countryFilter])) {
        $suffix = $countryMap[$countryFilter];
        if ($countryFilter === '美国') {
            $where[] = "(page_url LIKE :csfx AND page_url NOT LIKE :csfx2)";
            $params[':csfx']  = '%' . $suffix . '%';
            $params[':csfx2'] = '%.edu.au%';
        } else {
            $where[] = "page_url LIKE :csfx";
            $params[':csfx'] = '%' . $suffix . '%';
        }
    } elseif ($countryFilter === '其他') {
        $where[] = "(page_url NOT LIKE '%.uk%' AND page_url NOT LIKE '%.edu%' AND page_url NOT LIKE '%.ca%' AND page_url NOT LIKE '%.de%' AND page_url NOT LIKE '%.fr%' AND page_url NOT LIKE '%.jp%' AND page_url NOT LIKE '%.sg%' AND page_url NOT LIKE '%.cn%')";
    }
}

$whereClause = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';

$orderClause = match ($sort) {
    'deadline' => 'ORDER BY deadline_date IS NULL, deadline_date ASC',
    default    => 'ORDER BY created_at DESC',
};

// ---------- Count total ----------
$countSql = "SELECT COUNT(*) FROM admission_pages {$whereClause}";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

// ---------- Fetch rows ----------
$dataSql = "SELECT * FROM admission_pages {$whereClause} {$orderClause}";
$dataStmt = $pdo->prepare($dataSql);
$dataStmt->execute($params);
$allRows = $dataStmt->fetchAll();

// ---------- Exam filter (PHP side) ----------
if ($examFilter !== '全部') {
    $allRows = array_values(array_filter($allRows, static function (array $row) use ($examFilter): bool {
        $snippet = $row['requirement_snippet'] ?? '';
        $patterns = [
            'IELTS' => '/\bIELTS\b|雅思/i',
            'TOEFL' => '/\bTOEFL\b|托福/i',
            'SAT'   => '/\bSAT\b/i',
            'ACT'   => '/\bACT\b/i',
            'GRE'   => '/\bGRE\b/i',
            'GMAT'  => '/\bGMAT\b/i',
        ];
        if (!isset($patterns[$examFilter])) {
            return true;
        }
        return (bool) preg_match($patterns[$examFilter], $snippet);
    }));
    $total = count($allRows);
}

// ---------- Pagination ----------
$totalPages = max(1, (int) ceil($total / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;
$rows       = array_slice($allRows, $offset, $perPage);

// ---------- Enrich rows ----------
$enrichedRows = [];
foreach ($rows as $row) {
    $snippet  = $row['requirement_snippet'] ?? '';
    $deadline = null;
    if ($row['deadline_date'] !== null) {
        $deadline = DateTimeImmutable::createFromFormat('Y-m-d', $row['deadline_date']) ?: null;
    }
    $enrichedRows[] = [
        'id'                 => $row['id'],
        'university_home'    => $row['university_home'] ?? '',
        'page_title'         => $row['page_title'] ?? 'Admissions',
        'page_url'           => $row['page_url'] ?? '',
        'requirement_snippet'=> $snippet,
        'country'            => $row['country'] ?? detectCountryFromUrl($row['page_url'] ?? ''),
        'language_tags'      => detectLanguageTags($snippet),
        'deadline'           => $deadline,
        'status'             => statusText($deadline),
    ];
}

function detectCountryFromUrl(string $url): string
{
    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    if ($host === '') {
        return '未知';
    }
    $map = [
        '.uk'     => '英国',
        '.ac.uk'  => '英国',
        '.edu'    => '美国',
        '.edu.au' => '澳大利亚',
        '.ca'     => '加拿大',
        '.de'     => '德国',
        '.fr'     => '法国',
        '.jp'     => '日本',
        '.sg'     => '新加坡',
        '.cn'     => '中国',
    ];
    foreach ($map as $suffix => $country) {
        if (str_ends_with($host, $suffix)) {
            return $country;
        }
    }
    return '其他';
}

// ---------- Build filter dropdowns ----------
$countryStmt = $pdo->query("SELECT DISTINCT country FROM admission_pages WHERE country IS NOT NULL AND country != '' ORDER BY country");
$dbCountries = $countryStmt->fetchAll(PDO::FETCH_COLUMN);
$hasOthers = (bool) $pdo->query("SELECT COUNT(*) FROM admission_pages WHERE country IS NULL OR country = ''")->fetchColumn();
$countries = array_merge(['全部'], $dbCountries);
if ($hasOthers) {
    $countries[] = '其他';
}

$exams = ['全部', 'IELTS', 'TOEFL', 'SAT', 'ACT', 'GRE', 'GMAT'];

// ---------- Check if DB has data ----------
$dbCount = (int) $pdo->query("SELECT COUNT(*) FROM admission_pages")->fetchColumn();
$hasData = $dbCount > 0;

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>全球大学招生要求汇总 - UniData 留学数据平台</title>
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
            <a href="index.php" class="px-3 py-2 rounded-xl text-sm font-semibold bg-brand-50 text-brand-700 transition-colors">
                <i class="fa-solid fa-magnifying-glass mr-1.5"></i><span class="hidden sm:inline">招生信息</span>
            </a>
            <a href="match.php" class="px-3 py-2 rounded-xl text-sm font-medium text-slate-500 hover:text-brand-600 hover:bg-brand-50/50 transition-colors">
                <i class="fa-solid fa-wand-magic-sparkles mr-1.5"></i><span class="hidden sm:inline">智能匹配</span>
            </a>

            <div class="h-6 w-px bg-slate-200 mx-1 hidden sm:block"></div>

            <div class="flex items-center gap-2 text-xs text-slate-400 hidden lg:flex">
                <span class="px-2 py-1 rounded-lg bg-brand-50 text-brand-700 font-semibold"><?php echo number_format($dbCount); ?></span>
                <span>条数据</span>
            </div>

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
    <!-- Hero Section -->
    <div class="mb-8 animate-fade-in">
        <div class="bg-gradient-to-r from-brand-600 via-brand-700 to-blue-700 rounded-3xl p-8 sm:p-10 text-white shadow-2xl shadow-brand-500/20 relative overflow-hidden">
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 right-0 w-64 h-64 bg-white rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 left-0 w-48 h-48 bg-white rounded-full blur-3xl"></div>
            </div>
            <div class="relative">
                <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight mb-3">
                    <i class="fa-solid fa-globe mr-3"></i>全球大学招生要求
                </h1>
                <p class="text-brand-100 text-lg max-w-2xl">
                    汇聚世界顶尖大学的招生信息，智能筛选匹配你的目标院校。支持按国家、考试类型、截止日期多维度检索。
                </p>
                <div class="mt-5 flex flex-wrap gap-3 text-sm">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white/15 backdrop-blur">
                        <i class="fa-solid fa-check text-emerald-300"></i> 多国数据
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white/15 backdrop-blur">
                        <i class="fa-solid fa-check text-emerald-300"></i> 实时更新
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white/15 backdrop-blur">
                        <i class="fa-solid fa-check text-emerald-300"></i> 智能匹配
                    </span>
                </div>
            </div>
        </div>
    </div>

    <?php if (!$hasData): ?>
        <div class="bg-amber-50/80 backdrop-blur border border-amber-200 rounded-3xl p-8 text-center animate-fade-in">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-amber-100 flex items-center justify-center">
                <i class="fa-solid fa-database text-amber-600 text-2xl"></i>
            </div>
            <p class="text-amber-800 font-bold text-xl mb-2">数据库中暂无数据</p>
            <p class="text-amber-700 mb-5">请先运行爬虫获取数据，然后导入数据库：</p>
            <div class="bg-slate-900 text-green-400 text-left p-5 rounded-2xl inline-block text-sm font-mono shadow-lg">
                <span class="text-slate-500"># 1. 初始化数据库</span><br>
                php init_db.php<br><br>
                <span class="text-slate-500"># 2. 运行爬虫（需要有 Python 环境）</span><br>
                pip install httpx beautifulsoup4<br>
                python py_algorithm/Spider/main.py<br><br>
                <span class="text-slate-500"># 3. 导入 CSV 数据</span><br>
                php import_csv.php
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mt-6">
        <!-- Sidebar -->
        <aside class="lg:col-span-1">
            <form method="get" class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/60 shadow-lg shadow-slate-200/30 p-6 space-y-5 sticky top-28 animate-slide-up">
                <div class="flex items-center gap-2 mb-1">
                    <div class="w-8 h-8 rounded-lg bg-brand-100 flex items-center justify-center">
                        <i class="fa-solid fa-sliders text-brand-600 text-sm"></i>
                    </div>
                    <h2 class="text-base font-bold text-slate-800">筛选条件</h2>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2">搜索关键词</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <i class="fa-solid fa-magnifying-glass text-slate-400 text-sm"></i>
                        </div>
                        <input
                            type="text"
                            name="q"
                            value="<?php echo e($q); ?>"
                            placeholder="学校、要求、考试..."
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border-2 border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-500/10 transition-all duration-200 outline-none text-sm"
                        >
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2">国家/地区</label>
                    <select name="country" class="w-full py-2.5 pl-4 pr-10 rounded-xl border-2 border-slate-200 bg-slate-50 text-slate-900 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-500/10 transition-all duration-200 outline-none text-sm appearance-none cursor-pointer" style="background-image: url('data:image/svg+xml;charset=UTF-8,%3csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%2394a3b8%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22%3e%3cpolyline points=%226 9 12 15 18 9%22%3e%3c/polyline%3e%3c/svg%3e'); background-repeat: no-repeat; background-position: right 12px center; background-size: 16px;">
                        <?php foreach ($countries as $c): ?>
                            <option value="<?php echo e($c); ?>" <?php echo $countryFilter === $c ? 'selected' : ''; ?>>
                                <?php echo e($c); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2">语言/考试</label>
                    <select name="exam" class="w-full py-2.5 pl-4 pr-10 rounded-xl border-2 border-slate-200 bg-slate-50 text-slate-900 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-500/10 transition-all duration-200 outline-none text-sm appearance-none cursor-pointer" style="background-image: url('data:image/svg+xml;charset=UTF-8,%3csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%2394a3b8%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22%3e%3cpolyline points=%226 9 12 15 18 9%22%3e%3c/polyline%3e%3c/svg%3e'); background-repeat: no-repeat; background-position: right 12px center; background-size: 16px;">
                        <?php foreach ($exams as $exam): ?>
                            <option value="<?php echo e($exam); ?>" <?php echo $examFilter === $exam ? 'selected' : ''; ?>>
                                <?php echo e($exam); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2">排序</label>
                    <select name="sort" class="w-full py-2.5 pl-4 pr-10 rounded-xl border-2 border-slate-200 bg-slate-50 text-slate-900 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-500/10 transition-all duration-200 outline-none text-sm appearance-none cursor-pointer" style="background-image: url('data:image/svg+xml;charset=UTF-8,%3csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%2394a3b8%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22%3e%3cpolyline points=%226 9 12 15 18 9%22%3e%3c/polyline%3e%3c/svg%3e'); background-repeat: no-repeat; background-position: right 12px center; background-size: 16px;">
                        <option value="updated" <?php echo $sort === 'updated' ? 'selected' : ''; ?>>最新更新</option>
                        <option value="deadline" <?php echo $sort === 'deadline' ? 'selected' : ''; ?>>按截止日期</option>
                    </select>
                </div>

                <button class="w-full py-3 rounded-xl bg-gradient-to-r from-brand-600 to-blue-600 text-white font-semibold text-sm shadow-lg shadow-brand-500/20 hover:shadow-xl hover:shadow-brand-500/25 hover:from-brand-700 hover:to-blue-700 active:scale-[0.98] transition-all duration-200">
                    <i class="fa-solid fa-filter mr-2"></i>应用筛选
                </button>
            </form>
        </aside>

        <!-- Results -->
        <section class="lg:col-span-3">
            <!-- Stats Bar -->
            <div class="flex flex-wrap items-center justify-between gap-3 mb-5 animate-fade-in">
                <div class="flex items-center gap-3">
                    <p class="text-sm font-semibold text-slate-700">
                        共 <span class="text-brand-700 font-extrabold text-lg"><?php echo $total; ?></span> 条结果
                    </p>
                    <span class="text-xs text-slate-400">第 <?php echo $page; ?>/<?php echo $totalPages; ?> 页</span>
                </div>
                <?php if ($hasData): ?>
                    <div class="flex items-center gap-2 text-xs text-slate-400">
                        <i class="fa-solid fa-circle-check text-emerald-500"></i> 数据来源：MySQL 数据库
                    </div>
                <?php endif; ?>
            </div>

            <!-- Empty State -->
            <?php if ($enrichedRows === []): ?>
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/60 p-12 text-center animate-fade-in">
                    <div class="w-20 h-20 mx-auto mb-5 rounded-2xl bg-slate-100 flex items-center justify-center">
                        <i class="fa-solid fa-inbox text-slate-400 text-3xl"></i>
                    </div>
                    <p class="text-slate-500 font-medium text-lg">未找到符合条件的数据</p>
                    <p class="text-slate-400 text-sm mt-1">请尝试调整筛选条件或关键词</p>
                </div>
            <?php endif; ?>

            <!-- Card List -->
            <?php foreach ($enrichedRows as $row): ?>
                <article class="bg-white/80 backdrop-blur-xl rounded-2xl border border-slate-200/60 p-5 sm:p-6 mb-4 shadow-sm hover:shadow-lg hover:border-brand-200/50 transition-all duration-300 group animate-slide-up">
                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start gap-2 mb-1">
                                <h3 class="text-lg font-bold text-slate-900 leading-snug group-hover:text-brand-700 transition-colors">
                                    <?php echo e($row['page_title']); ?>
                                </h3>
                            </div>
                            <p class="text-sm text-slate-500 flex items-center gap-1.5">
                                <i class="fa-solid fa-building-columns text-xs"></i>
                                <?php echo e($row['university_home']); ?>
                            </p>

                            <div class="mt-3 flex flex-wrap gap-1.5 text-xs">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-brand-50 text-brand-700 font-medium">
                                    <i class="fa-solid fa-globe text-[10px]"></i> <?php echo e($row['country']); ?>
                                </span>
                                <?php foreach ($row['language_tags'] as $tag): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 font-medium">
                                        <?php echo e($tag); ?>
                                    </span>
                                <?php endforeach; ?>
                                <?php if ($row['language_tags'] === []): ?>
                                    <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-500 text-xs">考试要求待确认</span>
                                <?php endif; ?>
                            </div>

                            <p class="text-sm text-slate-600 mt-4 leading-relaxed line-clamp-3">
                                <?php echo e($row['requirement_snippet']); ?>
                            </p>
                        </div>

                        <div class="md:text-right shrink-0 flex md:flex-col flex-row items-center md:items-end gap-3 flex-wrap">
                            <div>
                                <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-semibold <?php echo e($row['status']['bg']); ?>">
                                    <span class="w-1.5 h-1.5 rounded-full <?php echo str_contains($row['status']['bg'], 'emerald') ? 'bg-emerald-500' : (str_contains($row['status']['bg'], 'amber') ? 'bg-amber-500' : 'bg-gray-400'); ?>"></span>
                                    <?php echo e($row['status']['text']); ?>
                                </span>
                                <p class="text-xs text-slate-400 mt-1.5">
                                    <?php echo $row['deadline'] instanceof DateTimeImmutable ? e($row['deadline']->format('Y-m-d')) : '截止日期未识别'; ?>
                                </p>
                            </div>
                            <a
                                href="<?php echo e($row['page_url']); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-gradient-to-r from-brand-600 to-blue-600 text-white text-sm font-semibold shadow-md shadow-brand-500/15 hover:shadow-lg hover:shadow-brand-500/25 hover:from-brand-700 hover:to-blue-700 active:scale-95 transition-all duration-200"
                            >
                                访问原始页面 <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                            </a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="mt-8 flex flex-wrap justify-center gap-2 animate-fade-in">
                    <?php
                    $params = $_GET;
                    unset($params['page']);
                    $baseQuery = http_build_query($params);
                    $baseUrl = '?' . ($baseQuery !== '' ? $baseQuery . '&' : '');
                    ?>
                    <?php if ($page > 1): ?>
                        <a href="<?php echo $baseUrl . 'page=' . ($page - 1); ?>" class="px-4 py-2.5 rounded-xl bg-white border-2 border-slate-200 text-slate-600 hover:border-brand-300 hover:text-brand-600 transition-all text-sm font-medium">
                            <i class="fa-solid fa-chevron-left mr-1"></i>上一页
                        </a>
                    <?php endif; ?>

                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    for ($i = $startPage; $i <= $endPage; $i++):
                        $isActive = $i === $page;
                    ?>
                        <a
                            href="<?php echo $baseUrl . 'page=' . $i; ?>"
                            class="w-10 h-10 flex items-center justify-center rounded-xl text-sm font-semibold transition-all <?php echo $isActive ? 'bg-gradient-to-br from-brand-600 to-blue-600 text-white shadow-lg shadow-brand-500/25' : 'bg-white border-2 border-slate-200 text-slate-600 hover:border-brand-300'; ?>"
                        >
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="<?php echo $baseUrl . 'page=' . ($page + 1); ?>" class="px-4 py-2.5 rounded-xl bg-white border-2 border-slate-200 text-slate-600 hover:border-brand-300 hover:text-brand-600 transition-all text-sm font-medium">
                            下一页<i class="fa-solid fa-chevron-right ml-1"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<footer class="border-t border-slate-200/60 bg-white/50 backdrop-blur mt-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6 text-center text-xs text-slate-400">
        &copy; <?php echo date('Y'); ?> UniData · 全球留学数据平台 · 数据仅供参考，请以各大学官网信息为准
    </div>
</footer>
</body>
</html>
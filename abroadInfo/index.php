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
        return ['text' => '待确认', 'class' => 'text-slate-500'];
    }

    $today = new DateTimeImmutable('today');
    $days  = (int) $today->diff($deadline)->format('%r%a');

    if ($days < 0) {
        return ['text' => '已截止', 'class' => 'text-gray-500'];
    }
    if ($days <= 20) {
        return ['text' => '即将截止', 'class' => 'text-amber-600'];
    }

    return ['text' => '开放中', 'class' => 'text-emerald-600'];
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
        // Match page_url domain suffix
        if ($countryFilter === '美国') {
            $where[] = "(page_url LIKE :csfx AND page_url NOT LIKE :csfx2)";
            $params[':csfx']  = '%' . $suffix . '%';
            $params[':csfx2'] = '%.edu.au%';
        } else {
            $where[] = "page_url LIKE :csfx";
            $params[':csfx'] = '%' . $suffix . '%';
        }
    } elseif ($countryFilter === '其他') {
        // Match URLs that don't match any known suffix
        $where[] = "(page_url NOT LIKE '%.uk%' AND page_url NOT LIKE '%.edu%' AND page_url NOT LIKE '%.ca%' AND page_url NOT LIKE '%.de%' AND page_url NOT LIKE '%.fr%' AND page_url NOT LIKE '%.jp%' AND page_url NOT LIKE '%.sg%' AND page_url NOT LIKE '%.cn%')";
    }
}

$whereClause = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';

// Exam filter is done in PHP (requires regex on snippet text)
// So we fetch all matching rows and filter in PHP for exam

// Sort
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

// ---------- Exam filter (PHP side, regex on requirement_snippet) ----------
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
// Add "其他" for unknown
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
    <title>全球大学招生要求汇总 - UniData</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-100 text-slate-900">
<nav class="bg-white/95 backdrop-blur border-b border-slate-200 sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
        <h1 class="text-lg md:text-xl font-bold text-sky-700">
            <i class="fa-solid fa-graduation-cap mr-2"></i>UniData 招生要求平台
        </h1>
        <div class="flex items-center gap-4">
            <a href="match.php" class="text-sm text-slate-600 hover:text-sky-700">
                <i class="fa-solid fa-wand-magic-sparkles mr-1"></i>智能匹配
            </a>
            <div class="flex items-center gap-3">
            <span class="text-xs text-slate-400 hidden sm:inline">数据库记录: <?php echo $dbCount; ?> 条</span>
            <?php if (isLoggedIn()): ?>
                <span class="text-sm text-slate-600">
                    <i class="fa-solid fa-user mr-1"></i><?php echo htmlspecialchars(currentUserAccount() ?? '', ENT_QUOTES, 'UTF-8'); ?>
                </span>
                <a href="logout.php" class="text-sm text-slate-500 hover:text-red-600 transition">
                    <i class="fa-solid fa-right-from-bracket mr-1"></i>退出
                </a>
            <?php else: ?>
                <a href="login.php" class="text-sm text-sky-600 hover:text-sky-800 transition">
                    <i class="fa-solid fa-right-to-bracket mr-1"></i>登录
                </a>
                <a href="register.php" class="text-sm px-3 py-1 rounded-lg bg-sky-600 text-white hover:bg-sky-700 transition">
                    注册
                </a>
            <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<main class="max-w-7xl mx-auto px-4 py-8">
    <?php if (!$hasData): ?>
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 text-center">
            <p class="text-amber-800 font-semibold text-lg mb-2">
                <i class="fa-solid fa-triangle-exclamation mr-2"></i>数据库中暂无数据
            </p>
            <p class="text-amber-700 mb-3">请先运行爬虫获取数据，然后导入数据库：</p>
            <div class="bg-slate-900 text-green-400 text-left p-4 rounded-lg inline-block text-sm font-mono">
                # 1. 初始化数据库<br>
                php init_db.php<br><br>
                # 2. 运行爬虫（需要有 Python 环境）<br>
                pip install httpx beautifulsoup4<br>
                python py_algorithm/Spider/main.py<br><br>
                # 3. 导入 CSV 数据<br>
                php import_csv.php
            </div>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <aside class="lg:col-span-1">
            <form method="get" class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-4">
                <h2 class="text-base font-semibold">筛选条件</h2>

                <div>
                    <label class="text-sm text-slate-600">关键词</label>
                    <input
                        type="text"
                        name="q"
                        value="<?php echo e($q); ?>"
                        placeholder="学校、要求、考试..."
                        class="mt-1 w-full rounded-lg border-slate-300 focus:border-sky-500 focus:ring-sky-500"
                    >
                </div>

                <div>
                    <label class="text-sm text-slate-600">国家/地区</label>
                    <select name="country" class="mt-1 w-full rounded-lg border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                        <?php foreach ($countries as $c): ?>
                            <option value="<?php echo e($c); ?>" <?php echo $countryFilter === $c ? 'selected' : ''; ?>>
                                <?php echo e($c); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="text-sm text-slate-600">语言/考试要求</label>
                    <select name="exam" class="mt-1 w-full rounded-lg border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                        <?php foreach ($exams as $exam): ?>
                            <option value="<?php echo e($exam); ?>" <?php echo $examFilter === $exam ? 'selected' : ''; ?>>
                                <?php echo e($exam); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="text-sm text-slate-600">排序方式</label>
                    <select name="sort" class="mt-1 w-full rounded-lg border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                        <option value="updated" <?php echo $sort === 'updated' ? 'selected' : ''; ?>>最新更新</option>
                        <option value="deadline" <?php echo $sort === 'deadline' ? 'selected' : ''; ?>>按截止日期排序</option>
                    </select>
                </div>

                <button class="w-full py-2 rounded-lg bg-sky-600 text-white hover:bg-sky-700 transition">应用筛选</button>
            </form>

            <div class="mt-4 bg-white border border-slate-200 rounded-2xl p-4 text-sm text-slate-600">
                <?php if ($hasData): ?>
                    <p><i class="fa-solid fa-database text-sky-600 mr-1"></i> 数据来源：MySQL 数据库</p>
                    <p class="mt-1 text-xs text-slate-400">admission_pages 表</p>
                <?php else: ?>
                    <p><i class="fa-solid fa-circle-exclamation text-amber-600 mr-1"></i> 无数据，请先运行爬虫并导入。</p>
                <?php endif; ?>
            </div>
        </aside>

        <section class="lg:col-span-3">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                <p class="text-sm text-slate-600">共匹配 <?php echo $total; ?> 条记录，第 <?php echo $page; ?>/<?php echo $totalPages; ?> 页</p>
            </div>

            <?php if ($enrichedRows === []): ?>
                <div class="bg-white border border-slate-200 rounded-2xl p-8 text-center text-slate-500">
                    未找到符合条件的数据，请调整筛选条件。
                </div>
            <?php endif; ?>

            <?php foreach ($enrichedRows as $row): ?>
                <article class="bg-white border border-slate-200 rounded-2xl p-5 mb-4 shadow-sm hover:shadow-md transition">
                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                        <div class="min-w-0">
                            <h3 class="text-lg font-semibold text-slate-900 break-words"><?php echo e($row['page_title']); ?></h3>
                            <p class="text-sm text-slate-500 mt-1"><?php echo e($row['university_home']); ?></p>

                            <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                <span class="px-2 py-1 rounded bg-sky-50 text-sky-700"><?php echo e($row['country']); ?></span>
                                <?php foreach ($row['language_tags'] as $tag): ?>
                                    <span class="px-2 py-1 rounded bg-indigo-50 text-indigo-700"><?php echo e($tag); ?></span>
                                <?php endforeach; ?>
                                <?php if ($row['language_tags'] === []): ?>
                                    <span class="px-2 py-1 rounded bg-slate-100 text-slate-600">考试要求待确认</span>
                                <?php endif; ?>
                            </div>

                            <p class="text-sm text-slate-700 mt-4 leading-6">
                                <?php echo e($row['requirement_snippet']); ?>
                            </p>
                        </div>

                        <div class="md:text-right shrink-0">
                            <p class="text-sm font-medium <?php echo e($row['status']['class']); ?>">
                                <?php echo e($row['status']['text']); ?>
                            </p>
                            <p class="text-xs text-slate-500 mt-1">
                                <?php echo $row['deadline'] instanceof DateTimeImmutable ? e($row['deadline']->format('Y-m-d')) : '截止日期未识别'; ?>
                            </p>
                            <a
                                href="<?php echo e($row['page_url']); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-block mt-3 px-4 py-2 rounded-lg bg-sky-600 text-white text-sm hover:bg-sky-700"
                            >
                                访问原始页面
                            </a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>

            <?php if ($totalPages > 1): ?>
                <div class="mt-6 flex flex-wrap gap-2">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php
                        $params = $_GET;
                        $params['page'] = $i;
                        $link = '?' . http_build_query($params);
                        ?>
                        <a
                            href="<?php echo e($link); ?>"
                            class="px-3 py-1.5 rounded border text-sm <?php echo $i === $page ? 'bg-sky-600 text-white border-sky-600' : 'bg-white text-slate-700 border-slate-300 hover:border-sky-500'; ?>"
                        >
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>
</body>
</html>
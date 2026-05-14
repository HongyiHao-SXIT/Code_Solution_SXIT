<?php

declare(strict_types=1);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function detectCountry(string $url): string
{
    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    if ($host === '') {
        return '未知';
    }

    $map = [
        '.uk' => '英国',
        '.ac.uk' => '英国',
        '.edu' => '美国',
        '.edu.au' => '澳大利亚',
        '.ca' => '加拿大',
        '.de' => '德国',
        '.fr' => '法国',
        '.jp' => '日本',
        '.sg' => '新加坡',
        '.cn' => '中国',
    ];

    foreach ($map as $suffix => $country) {
        if (str_ends_with($host, $suffix)) {
            return $country;
        }
    }

    return '其他';
}

function detectLanguageTags(string $text): array
{
    $tags = [];
    $checks = [
        'IELTS' => '/\bIELTS\b|雅思/i',
        'TOEFL' => '/\bTOEFL\b|托福/i',
        'SAT' => '/\bSAT\b/i',
        'ACT' => '/\bACT\b/i',
        'GRE' => '/\bGRE\b/i',
        'GMAT' => '/\bGMAT\b/i',
    ];

    foreach ($checks as $label => $pattern) {
        if (preg_match($pattern, $text)) {
            $tags[] = $label;
        }
    }

    return $tags;
}

function parseDeadline(string $text): ?DateTimeImmutable
{
    if (preg_match('/(20\d{2})[\/\-.](\d{1,2})[\/\-.](\d{1,2})/', $text, $matches)) {
        $date = sprintf('%04d-%02d-%02d', (int) $matches[1], (int) $matches[2], (int) $matches[3]);
        return DateTimeImmutable::createFromFormat('Y-m-d', $date) ?: null;
    }

    return null;
}

function statusByDeadline(?DateTimeImmutable $deadline): array
{
    if ($deadline === null) {
        return ['text' => '待确认', 'class' => 'text-slate-500'];
    }

    $today = new DateTimeImmutable('today');
    $days = (int) $today->diff($deadline)->format('%r%a');

    if ($days < 0) {
        return ['text' => '已截止', 'class' => 'text-gray-500'];
    }
    if ($days <= 20) {
        return ['text' => '即将截止', 'class' => 'text-amber-600'];
    }

    return ['text' => '开放中', 'class' => 'text-emerald-600'];
}

function loadAdmissions(string $baseDir): array
{
    $candidateFiles = [
        $baseDir . DIRECTORY_SEPARATOR . 'py_algorithm' . DIRECTORY_SEPARATOR . 'Spider' . DIRECTORY_SEPARATOR . 'admission_requirements.csv',
        $baseDir . DIRECTORY_SEPARATOR . 'Spider' . DIRECTORY_SEPARATOR . 'admission_requirements.csv',
        $baseDir . DIRECTORY_SEPARATOR . 'admission_requirements.csv',
    ];

    $filePath = null;
    foreach ($candidateFiles as $path) {
        if (is_file($path)) {
            $filePath = $path;
            break;
        }
    }

    if ($filePath === null) {
        return [];
    }

    $rows = [];
    $fp = fopen($filePath, 'rb');
    if ($fp === false) {
        return [];
    }

    $header = fgetcsv($fp);
    if ($header === false) {
        fclose($fp);
        return [];
    }

    $header = array_map(static fn($h) => trim((string) $h), $header);

    while (($line = fgetcsv($fp)) !== false) {
        if (count($line) !== count($header)) {
            continue;
        }

        $item = array_combine($header, $line);
        if ($item === false) {
            continue;
        }

        $home = trim((string) ($item['university_home'] ?? ''));
        $title = trim((string) ($item['page_title'] ?? ''));
        $url = trim((string) ($item['page_url'] ?? ''));
        $snippet = trim((string) ($item['requirement_snippet'] ?? ''));

        if ($url === '' || $snippet === '') {
            continue;
        }

        $deadline = parseDeadline($snippet);
        $rows[] = [
            'university_home' => $home,
            'page_title' => $title !== '' ? $title : 'Admissions',
            'page_url' => $url,
            'requirement_snippet' => $snippet,
            'country' => detectCountry($url),
            'language_tags' => detectLanguageTags($snippet),
            'deadline' => $deadline,
            'status' => statusByDeadline($deadline),
        ];
    }

    fclose($fp);
    return $rows;
}

$data = loadAdmissions(__DIR__);

if ($data === []) {
    $data = [
        [
            'university_home' => 'https://www.ox.ac.uk/admissions',
            'page_title' => 'Undergraduate admissions and applications',
            'page_url' => 'https://www.ox.ac.uk/admissions/undergraduate',
            'requirement_snippet' => 'Typical offers include IELTS and course-specific grade requirements. Deadlines vary by program and college.',
            'country' => '英国',
            'language_tags' => ['IELTS'],
            'deadline' => null,
            'status' => ['text' => '待确认', 'class' => 'text-slate-500'],
        ],
        [
            'university_home' => 'https://www.mit.edu/admissions-aid/',
            'page_title' => 'First-year application',
            'page_url' => 'https://mitadmissions.org/apply/firstyear/',
            'requirement_snippet' => 'Application requires transcripts and testing policy details. TOEFL is considered for non-native speakers.',
            'country' => '美国',
            'language_tags' => ['TOEFL'],
            'deadline' => null,
            'status' => ['text' => '待确认', 'class' => 'text-slate-500'],
        ],
        [
            'university_home' => 'https://www.ucl.ac.uk/prospective-students/',
            'page_title' => 'International students entry requirements',
            'page_url' => 'https://www.ucl.ac.uk/prospective-students/international',
            'requirement_snippet' => 'International applicants should check country-specific academic requirements, English tests, and application timelines.',
            'country' => '英国',
            'language_tags' => ['IELTS', 'TOEFL'],
            'deadline' => null,
            'status' => ['text' => '待确认', 'class' => 'text-slate-500'],
        ],
    ];
}

$q = trim((string) ($_GET['q'] ?? ''));
$countryFilter = trim((string) ($_GET['country'] ?? '全部'));
$examFilter = trim((string) ($_GET['exam'] ?? '全部'));
$sort = trim((string) ($_GET['sort'] ?? 'updated'));
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 8;

$countries = ['全部'];
foreach ($data as $row) {
    if (!in_array($row['country'], $countries, true)) {
        $countries[] = $row['country'];
    }
}
sort($countries);
array_unshift($countries, '全部');
$countries = array_values(array_unique($countries));

$exams = ['全部', 'IELTS', 'TOEFL', 'SAT', 'ACT', 'GRE', 'GMAT'];

$filtered = array_values(array_filter(
    $data,
    static function (array $row) use ($q, $countryFilter, $examFilter): bool {
        if ($countryFilter !== '全部' && $row['country'] !== $countryFilter) {
            return false;
        }

        if ($examFilter !== '全部' && !in_array($examFilter, $row['language_tags'], true)) {
            return false;
        }

        if ($q !== '') {
            $haystack = strtolower(
                $row['page_title'] . ' ' .
                $row['requirement_snippet'] . ' ' .
                $row['page_url'] . ' ' .
                $row['university_home']
            );
            if (!str_contains($haystack, strtolower($q))) {
                return false;
            }
        }

        return true;
    }
));

usort(
    $filtered,
    static function (array $a, array $b) use ($sort): int {
        if ($sort === 'deadline') {
            $ad = $a['deadline'] instanceof DateTimeImmutable ? $a['deadline']->getTimestamp() : PHP_INT_MAX;
            $bd = $b['deadline'] instanceof DateTimeImmutable ? $b['deadline']->getTimestamp() : PHP_INT_MAX;
            return $ad <=> $bd;
        }

        return strcmp($a['page_title'], $b['page_title']);
    }
);

$total = count($filtered);
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$rows = array_slice($filtered, $offset, $perPage);

$hasCsv = is_file(__DIR__ . DIRECTORY_SEPARATOR . 'py_algorithm' . DIRECTORY_SEPARATOR . 'Spider' . DIRECTORY_SEPARATOR . 'admission_requirements.csv')
    || is_file(__DIR__ . DIRECTORY_SEPARATOR . 'Spider' . DIRECTORY_SEPARATOR . 'admission_requirements.csv')
    || is_file(__DIR__ . DIRECTORY_SEPARATOR . 'admission_requirements.csv');

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>全球大学招生要求汇总</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-100 text-slate-900">
<nav class="bg-white/95 backdrop-blur border-b border-slate-200 sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
        <h1 class="text-lg md:text-xl font-bold text-sky-700">
            <i class="fa-solid fa-graduation-cap mr-2"></i>UniData 招生要求平台
        </h1>
    </div>
</nav>

<main class="max-w-7xl mx-auto px-4 py-8">
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
                        <?php foreach ($countries as $country): ?>
                            <option value="<?php echo e($country); ?>" <?php echo $countryFilter === $country ? 'selected' : ''; ?>>
                                <?php echo e($country); ?>
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
                        <option value="updated" <?php echo $sort === 'updated' ? 'selected' : ''; ?>>按标题排序</option>
                        <option value="deadline" <?php echo $sort === 'deadline' ? 'selected' : ''; ?>>按截止日期排序</option>
                    </select>
                </div>

                <button class="w-full py-2 rounded-lg bg-sky-600 text-white hover:bg-sky-700 transition">应用筛选</button>
            </form>

            <div class="mt-4 bg-white border border-slate-200 rounded-2xl p-4 text-sm text-slate-600">
                <?php if ($hasCsv): ?>
                    <p><i class="fa-solid fa-circle-check text-emerald-600 mr-1"></i> 已检测到爬虫数据 CSV。</p>
                <?php else: ?>
                    <p><i class="fa-solid fa-triangle-exclamation text-amber-600 mr-1"></i> 未检测到 CSV，当前显示示例数据。</p>
                    <p class="mt-2">可运行命令：<br>python py_algorithm/Spider/main.py</p>
                <?php endif; ?>
            </div>
        </aside>

        <section class="lg:col-span-3">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                <p class="text-sm text-slate-600">共匹配 <?php echo $total; ?> 条记录，第 <?php echo $page; ?>/<?php echo $totalPages; ?> 页</p>
            </div>

            <?php if ($rows === []): ?>
                <div class="bg-white border border-slate-200 rounded-2xl p-8 text-center text-slate-500">
                    未找到符合条件的数据，请调整筛选条件。
                </div>
            <?php endif; ?>

            <?php foreach ($rows as $row): ?>
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

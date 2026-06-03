<?php

declare(strict_types=1);

/**
 * Import admission_requirements.csv into the admission_pages table.
 * Usage: php import_csv.php [path/to/admission_requirements.csv]
 */

require_once __DIR__ . '/db.php';

$defaultPaths = [
    __DIR__ . DIRECTORY_SEPARATOR . 'py_algorithm' . DIRECTORY_SEPARATOR . 'Spider' . DIRECTORY_SEPARATOR . 'admission_requirements.csv',
    __DIR__ . DIRECTORY_SEPARATOR . 'Spider' . DIRECTORY_SEPARATOR . 'admission_requirements.csv',
    __DIR__ . DIRECTORY_SEPARATOR . 'admission_requirements.csv',
];

$csvPath = $argv[1] ?? null;
if ($csvPath === null) {
    foreach ($defaultPaths as $path) {
        if (is_file($path)) {
            $csvPath = $path;
            break;
        }
    }
}

if ($csvPath === null || !is_file($csvPath)) {
    echo "Error: CSV file not found.\n";
    echo "Usage: php import_csv.php [path/to/admission_requirements.csv]\n";
    exit(1);
}

echo "Reading CSV: {$csvPath}\n";

$fp = fopen($csvPath, 'rb');
if ($fp === false) {
    echo "Error: Cannot open CSV file.\n";
    exit(1);
}

$header = fgetcsv($fp);
if ($header === false) {
    fclose($fp);
    echo "Error: CSV file is empty.\n";
    exit(1);
}

$header = array_map(static fn($h) => trim((string) $h), $header);

$pdo = db();

// Prepare insert statement
$sql = "INSERT IGNORE INTO admission_pages 
    (university_home, page_title, page_url, requirement_snippet, source) 
    VALUES (:university_home, :page_title, :page_url, :requirement_snippet, 'spider')";

$stmt = $pdo->prepare($sql);

$imported = 0;
$skipped = 0;
$errors = 0;

// Helper to extract country from URL
function extractCountry(string $url): ?string
{
    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    if ($host === '') {
        return null;
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

    return null;
}

// Helper to extract deadline date from text
function extractDeadline(string $text): ?string
{
    if (preg_match('/(20\d{2})[\/\-.](\d{1,2})[\/\-.](\d{1,2})/', $text, $matches)) {
        return sprintf('%04d-%02d-%02d', (int) $matches[1], (int) $matches[2], (int) $matches[3]);
    }
    return null;
}

while (($line = fgetcsv($fp)) !== false) {
    if (count($line) !== count($header)) {
        $skipped++;
        continue;
    }

    $row = array_combine($header, $line);
    if ($row === false) {
        $skipped++;
        continue;
    }

    $universityHome = trim((string) ($row['university_home'] ?? ''));
    $pageTitle = trim((string) ($row['page_title'] ?? ''));
    $pageUrl = trim((string) ($row['page_url'] ?? ''));
    $snippet = trim((string) ($row['requirement_snippet'] ?? ''));

    if ($pageUrl === '' || $snippet === '') {
        $skipped++;
        continue;
    }

    $country = extractCountry($pageUrl);
    $deadline = extractDeadline($snippet);

    try {
        $stmt->execute([
            ':university_home' => $universityHome ?: null,
            ':page_title' => $pageTitle ?: 'Admissions',
            ':page_url' => $pageUrl,
            ':requirement_snippet' => $snippet,
        ]);

        // Update country and deadline if we detected them
        if ($country !== null || $deadline !== null) {
            $updateSql = "UPDATE admission_pages SET ";
            $updateParams = [];
            $setClauses = [];

            if ($country !== null) {
                $setClauses[] = "country = :country";
                $updateParams[':country'] = $country;
            }
            if ($deadline !== null) {
                $setClauses[] = "deadline_date = :deadline";
                $updateParams[':deadline'] = $deadline;
            }

            $updateSql .= implode(', ', $setClauses) . " WHERE page_url = :page_url";
            $updateParams[':page_url'] = $pageUrl;

            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute($updateParams);
        }

        $imported++;
    } catch (Throwable $e) {
        echo "Error on row {$imported}: {$e->getMessage()}\n";
        $errors++;
    }
}

fclose($fp);

echo "\n=== Import Summary ===\n";
echo "Imported: {$imported}\n";
echo "Skipped: {$skipped}\n";
echo "Errors: {$errors}\n";
echo "Total in DB: " . $pdo->query("SELECT COUNT(*) FROM admission_pages")->fetchColumn() . "\n";
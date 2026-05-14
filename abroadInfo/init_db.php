<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

$sqlPath = __DIR__ . '/data.sql';
if (!is_file($sqlPath)) {
    http_response_code(500);
    echo "data.sql not found\n";
    exit(1);
}

$sql = file_get_contents($sqlPath);
if ($sql === false) {
    http_response_code(500);
    echo "Failed to read data.sql\n";
    exit(1);
}

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '123456';

$dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $host, $port);

try {
    $pdo = new PDO(
        $dsn,
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $pdo->exec($sql);

    echo "Database initialized successfully.\n";
    echo "You can now connect using db() from db.php.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "Database initialization failed: " . $e->getMessage() . "\n";
    exit(1);
}

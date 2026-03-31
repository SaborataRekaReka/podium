<?php

declare(strict_types=1);

// Секретный токен. Передавать в URL: ?token=<значение>
// Чтобы сменить токен — замените строку ниже на любую другую.
define('DOWNLOAD_TOKEN', 'f4a65d5259fb07bd07083cfa0c1952bac7bccda0970f27ed');

$token = $_GET['token'] ?? '';

if (!hash_equals(DOWNLOAD_TOKEN, $token)) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'message' => 'Forbidden'], JSON_UNESCAPED_UNICODE);
    exit;
}

$csvFile = __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'quiz-results.csv';

if (!file_exists($csvFile)) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'message' => 'File not found'], JSON_UNESCAPED_UNICODE);
    exit;
}

$filename = 'quiz-results-' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($csvFile));
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

readfile($csvFile);
exit;

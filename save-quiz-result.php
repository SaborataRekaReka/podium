<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$rawBody = file_get_contents('php://input');
$payload = [];

if (is_string($rawBody) && trim($rawBody) !== '') {
    $decoded = json_decode($rawBody, true);
    if (is_array($decoded)) {
        $payload = $decoded;
    }
}

if (!$payload && !empty($_POST)) {
    $payload = $_POST;
}

$normalize = static function ($value): string {
    if (!is_scalar($value)) {
        return '';
    }
    $text = trim((string) $value);
    $collapsed = preg_replace('/\s+/u', ' ', $text);
    return $collapsed === null ? $text : $collapsed;
};

$escapeCsvFormula = static function (string $value): string {
    if ($value !== '' && preg_match('/^[=+\-@]/', $value) === 1) {
        return "'" . $value;
    }
    return $value;
};

$fullName = $normalize($payload['fullName'] ?? '');
$phone = $normalize($payload['phone'] ?? '');
$email = $normalize($payload['email'] ?? '');

if ($fullName === '' || $phone === '' || $email === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Missing required fields'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Invalid email'], JSON_UNESCAPED_UNICODE);
    exit;
}

$correctAnswers = isset($payload['correctAnswers']) ? (int) $payload['correctAnswers'] : 0;
$wrongAnswers = isset($payload['wrongAnswers']) ? (int) $payload['wrongAnswers'] : 0;
$totalQuestions = isset($payload['totalQuestions']) ? (int) $payload['totalQuestions'] : 0;
$resultAccuracy = isset($payload['resultAccuracy']) ? (int) $payload['resultAccuracy'] : 0;
$source = $normalize($payload['source'] ?? 'podium-quiz');
$userAgent = $normalize($_SERVER['HTTP_USER_AGENT'] ?? '');
$remoteIp = $normalize($_SERVER['REMOTE_ADDR'] ?? '');

$csvFile = __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'quiz-results-f4a65d5259fb07bd.csv';
$isNewFile = !file_exists($csvFile) || filesize($csvFile) === 0;

$fh = fopen($csvFile, 'ab');
if ($fh === false) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Unable to open CSV file'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!flock($fh, LOCK_EX)) {
    fclose($fh);
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Unable to lock CSV file'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($isNewFile) {
    fputcsv($fh, [
        'submitted_at',
        'full_name',
        'phone',
        'email',
        'correct_answers',
        'wrong_answers',
        'total_questions',
        'accuracy_percent',
        'source',
        'remote_ip',
        'user_agent'
    ]);
}

fputcsv($fh, [
    gmdate('c'),
    $escapeCsvFormula($fullName),
    $escapeCsvFormula($phone),
    $escapeCsvFormula($email),
    $correctAnswers,
    $wrongAnswers,
    $totalQuestions,
    $resultAccuracy,
    $escapeCsvFormula($source),
    $escapeCsvFormula($remoteIp),
    $escapeCsvFormula($userAgent)
]);

fflush($fh);
flock($fh, LOCK_UN);
fclose($fh);

@chmod($csvFile, 0666);

echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);

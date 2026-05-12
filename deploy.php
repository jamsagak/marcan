<?php
// Marcan Auto-Deploy v1.0

$secret = 'marcan_deploy_2026_secret_key';

$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$payload = file_get_contents('php://input');

if (!$signature) {
    http_response_code(403);
    die('No signature');
}

$expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (!hash_equals($expected, $signature)) {
    http_response_code(403);
    die('Invalid signature');
}

$data = json_decode($payload, true);

if (($data['ref'] ?? '') !== 'refs/heads/main') {
    die('Not main branch, skipping.');
}

$repo_path = '/home/newmarcancom/public_html';
$log_file = '/home/newmarcancom/deploy.log';

$commands = [
    "cd $repo_path",
    'git fetch origin main',
    'git reset --hard origin/main',
];

$output = [];
$cmd = implode(' && ', $commands) . ' 2>&1';
exec($cmd, $output, $return_code);

$log_entry = date('Y-m-d H:i:s') . " | Return: $return_code\n" . implode("\n", $output) . "\n---\n";
file_put_contents($log_file, $log_entry, FILE_APPEND);

if ($return_code === 0) {
    echo 'Deploy successful';
} else {
    http_response_code(500);
    echo 'Deploy failed. Check logs.';
}

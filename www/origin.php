<?php
// preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("Access-Control-Max-Age: 86400"); // 24 heures
    http_response_code(200);
    exit();
}

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=utf-8");


function getClientUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 ? 'https' : 'http';
    
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
    
    $url = $protocol . '://' . $host;
    
    $port = $_SERVER['SERVER_PORT'] ?? '';
    if ($port && $port != 80 && $port != 443) {
        $url .= ':' . $port;
    }
    
    return $url;
}

$clientUrl = getClientUrl();

echo json_encode([
    'success' => true,
    'client_url' => $clientUrl,
    'domain' => parse_url($clientUrl, PHP_URL_HOST),
    'protocol' => parse_url($clientUrl, PHP_URL_SCHEME),
    'timestamp' => date('Y-m-d H:i:s')
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
<?php
/**
 * cors.php
 * Call this at the top of every API entry point (before any output).
 * Handles CORS + OPTIONS preflight for same-site XAMPP setups.
 */

// Catch any fatal error and return JSON instead of empty/HTML response
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode([
            'success' => false,
            'message' => 'Server error: ' . $err['message'] . ' in ' . basename($err['file']) . ':' . $err['line'],
        ]);
    }
});

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if ($origin !== '') {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
}

header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Max-Age: 3600');

// Answer preflight immediately
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

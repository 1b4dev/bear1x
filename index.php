<?php
require_once(__DIR__ . '/middleware/BFF.php');

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uriSegments = explode('/', trim($requestUri, '/'));

// Handles the options request when using React, delete if you set up with .htaccess
if (isset($uriSegments[1])) {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        header('Access-Control-Allow-Headers: Content-Type, Authorization, Origin');
        header('Access-Control-Max-Age: 3600');
        exit;
    }
}

// Get the url of proxied requests and start middleware
// If you have router like this in your backend index file you can merge with this
if (isset($uriSegments[0]) && $uriSegments[0] === 'bff') {
    array_shift($uriSegments);

    $bff = new BFFMiddleware();

    try {
        $bff->handleRequest($uriSegments);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Internal Server Error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Not Found']);
}

exit();
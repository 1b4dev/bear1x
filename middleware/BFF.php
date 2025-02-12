<?php

class BFFMiddleware {
    private $action;
    private $baseUrl;

    public function __construct() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $this->baseUrl = $protocol . '://' . $host;
    }

    // Forwarding api request without curl, because curl can cause problems in some servers
    public function handleRequest($uriSegments) {
        $this->action = $uriSegments[1] ?? '';
        // Incase no endpoint set
        if (empty($this->action)) {
            $this->returnJson(['error' => 'No endpoint specified'], 400);
            return;
        }
        // In case if there is cookie and token 
        $token = $_COOKIE['token'] ?? null;
        $backendUrl = $this->baseUrl . '/' . implode('/', $uriSegments);
        // You need adjust this according the your login endpoint
        $isLoginRequest = (end($uriSegments) === 'login');

        $contextOptions = [
            'http' => [
                'method' => $_SERVER['REQUEST_METHOD'],
                'header' => $this->getForwardHeaders($token),
                'content' => file_get_contents('php://input'),
                'ignore_errors' => true,
                'follow_location' => true
            ]
        ];

        $context = stream_context_create($contextOptions);
        $response = @file_get_contents($backendUrl, false, $context);

        if ($response === false) {
            $error = error_get_last();
            $this->returnJson(['error' => 'file_get_contents failed: ' . ($error['message'] ?? 'Unknown error')], 500);
            return;
        }

        $this->forwardResponse($http_response_header, $response, $isLoginRequest);
    }
    private function forwardResponse($responseHeadersRaw, $responseBody, $isLoginRequest = false) {
        $statusCode = 500;
        $responseHeaders = [];

        if (isset($responseHeadersRaw) && is_array($responseHeadersRaw)) {
            foreach ($responseHeadersRaw as $headerLine) {
                if (preg_match('#^HTTP/(?:\d+\.?\d*) (\d{3})#i', $headerLine, $matches)) {
                    $statusCode = intval($matches[1]);
                    http_response_code($statusCode);
                } else {
                    if (strpos($headerLine, 'Transfer-Encoding:') === 0) continue;
                    if (strpos($headerLine, 'Connection:') === 0) continue;
                    if (!empty($headerLine)) {
                        header($headerLine);
                    }
                }
            }
        } else {
            $this->returnJson(['error' => 'Could not parse response headers from backend', 'detail' => 'No headers received or invalid format'], 500);
            return;
        }

        $decodedBody = json_decode($responseBody, true);
        // You need to adjust 'token' according to your backend response
        if ($isLoginRequest && $statusCode === 200 && isset($decodedBody['token'])) {
            $token = $decodedBody['token'];
            // Seting cookie here with token
            setcookie(
                'token',
                $token,
                [
                    'expires' => time() + 86400,
                    'path' => '/',
                    'secure' => true,
                    'httponly' => false,
                    'samesite' => 'None'
                ]
            );
            // Removing token from backend response
            unset($decodedBody['token']);
            // Adding success message
            $cookieResponse = array_merge($decodedBody, ['cookie' => 'Login successful']);
            $responseBody = json_encode($cookieResponse);
        }
        // If your backend sends no response or sends response with errors this will trigger
        if (!empty($responseBody)) {
            if ($decodedBody === null && json_last_error() !== JSON_ERROR_NONE) {
                $plainResponse = strip_tags($responseBody);
                $this->returnJson([
                    'error' => 'API Error',
                    'message' => trim($plainResponse),
                    'status' => $statusCode
                ], $statusCode);
            } else {
                echo $responseBody;
            }
        } else {
            echo json_encode(null);
        }
    }

    private function getForwardHeaders($token) {
        $headers = [];
        $allowedHeaders = [
            'accept',
            'content-type',
            'authorization',
            'user-agent',
            'x-requested-with'
        ];

        $incomingHeaders = getallheaders();
        $forwardContentType = false;

        foreach ($incomingHeaders as $name => $value) {
            $lowercaseName = strtolower($name);
            if (in_array($lowercaseName, $allowedHeaders)) {
                $headers[] = "$name: $value";
                if ($lowercaseName === 'content-type') {
                    $forwardContentType = true; // Flag to indicate Content-Type is already forwarded
                }
            }
        }
        
        if ($token) {
            $headers[] = "Authorization: Bearer $token";
        }

        $headers[] = "Accept: application/json";
        if (!$forwardContentType) {
            $headers[] = "Content-Type: application/json";
        }

        return $headers;
    }
    // Returns the json data, if data string it sets to json array
    private function returnJson($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        if (is_string($data)) {
            echo json_encode(['message' => $data]);
        } else {
            echo json_encode($data);
        }
        exit;
    }
}

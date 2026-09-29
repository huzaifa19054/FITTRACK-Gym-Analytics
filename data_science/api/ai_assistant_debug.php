<?php
header('Content-Type: application/json; charset=utf-8');

$result = [
    'success' => false,
    'checks' => []
];

try {
    // 1. Check cURL
    $result['checks']['curl_extension'] = function_exists('curl_init');

    if (!function_exists('curl_init')) {
        throw new RuntimeException('PHP cURL extension is not enabled.');
    }

    // 2. Check Gemini API key without exposing it
    $apiKey = getenv('GEMINI_API_KEY');

    if (!$apiKey) {
        $apiKey = $_SERVER['GEMINI_API_KEY'] ?? '';
    }

    if (!$apiKey) {
        $apiKey = $_ENV['GEMINI_API_KEY'] ?? '';
    }

    $result['checks']['api_key_present'] = !empty($apiKey);

    if (!$apiKey) {
        throw new RuntimeException(
            'GEMINI_API_KEY is not visible to Apache/PHP. Restart Apache after setting the Windows environment variable.'
        );
    }

    // 3. Test the exact model endpoint used by FITTRACK
    $model = 'gemini-3.8-flash';

    $url =
        'https://generativelanguage.googleapis.com/v1beta/models/' .
        rawurlencode($model);

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            'x-goog-api-key: ' . $apiKey
        ]
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result['checks']['model'] = $model;
    $result['checks']['model_http_status'] = $httpCode;

    if ($response === false) {
        throw new RuntimeException(
            'cURL could not reach Google: ' . $curlError
        );
    }

    $json = json_decode($response, true);

    if ($httpCode < 200 || $httpCode >= 300) {
        $message =
            $json['error']['message']
            ?? 'Google returned an unknown error.';

        throw new RuntimeException(
            'Gemini model check failed (HTTP ' . $httpCode . '): ' . $message
        );
    }

    $result['checks']['model_available'] = true;

    // 4. Test an actual generateContent request
    $generateUrl =
        'https://generativelanguage.googleapis.com/v1beta/models/' .
        rawurlencode($model) .
        ':generateContent';

    $payload = [
        'contents' => [
            [
                'parts' => [
                    [
                        'text' => 'Reply with exactly: FITTRACK TEST OK'
                    ]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.1,
            'maxOutputTokens' => 20
        ]
    ];

    $ch = curl_init($generateUrl);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-goog-api-key: ' . $apiKey
        ],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);

    $generateResponse = curl_exec($ch);
    $generateCurlError = curl_error($ch);
    $generateHttpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result['checks']['generate_http_status'] = $generateHttpCode;

    if ($generateResponse === false) {
        throw new RuntimeException(
            'GenerateContent cURL failed: ' . $generateCurlError
        );
    }

    $generateJson = json_decode($generateResponse, true);

    if ($generateHttpCode < 200 || $generateHttpCode >= 300) {
        $message =
            $generateJson['error']['message']
            ?? 'Google returned an unknown generation error.';

        throw new RuntimeException(
            'GenerateContent failed (HTTP ' .
            $generateHttpCode .
            '): ' .
            $message
        );
    }

    $answer =
        $generateJson['candidates'][0]['content']['parts'][0]['text']
        ?? '';

    $result['checks']['generate_success'] = true;
    $result['checks']['test_answer'] = trim($answer);
    $result['success'] = true;

} catch (Throwable $e) {
    $result['error'] = $e->getMessage();
}

echo json_encode(
    $result,
    JSON_PRETTY_PRINT |
    JSON_UNESCAPED_SLASHES |
    JSON_UNESCAPED_UNICODE
);
?>

<?php

declare(strict_types=1);

namespace Vessot\Service\Operations;

class ShowService
{
    protected string $apiUrl;

    public function __construct(
        string $apiUrl
    ) {
        $this->apiUrl = $apiUrl;
    }

    public function execute(
        string $key,
        ?string $attribute,
        string $encryptionKey,
        callable $decryptCallback
    ): array {
        $token = getenv('VESSOT_INT_TOKEN');
        if ($token === false || empty($token)) {
            return [
                'code' => 0,
                'success' => false,
                'error' => 'VESSOT_INT_TOKEN environment variable not set',
                'value' => ''
            ];
        }

        $url = $this->apiUrl . '/show/' . urlencode($key);
        if ($attribute !== null) {
            $url .= '?attribute=' . urlencode($attribute);
        }

        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json'
            ],
            'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            return [
                'code' => 0,
                'success' => false,
                'error' => $response->get_error_message(),
                'value' => ''
            ];
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($statusCode === 200) {
            $responseData = json_decode($body, true);
            $encryptedValue = $responseData['value'] ?? '';

            if (!empty($encryptedValue)) {
                try {
                    $decryptedValue = $decryptCallback($encryptedValue);
                    return [
                        'code' => $statusCode,
                        'success' => true,
                        'error' => '',
                        'value' => $decryptedValue
                    ];
                } catch (\Exception $e) {
                    return [
                        'code' => $statusCode,
                        'success' => false,
                        'error' => 'Decryption failed: ' . $e->getMessage(),
                        'value' => ''
                    ];
                }
            }

            return [
                'code' => $statusCode,
                'success' => true,
                'error' => '',
                'value' => ''
            ];
        } else {
            $errorData = json_decode($body, true);
            return [
                'code' => $statusCode,
                'success' => false,
                'error' => $errorData['error'] ?? 'API request failed',
                'value' => ''
            ];
        }
    }
}

<?php

declare(strict_types=1);

namespace Vessot\Service\Operations;

class StoreService
{
    protected string $apiUrl;

    public function __construct(
        string $apiUrl
    ) {
        $this->apiUrl = $apiUrl;
    }

    public function execute(
        string $key,
        $value,
        callable $encryptCallback
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

        try {
            $encryptedValue = $encryptCallback($value);

            $response = wp_remote_post($this->apiUrl . '/store', [
                'body' => json_encode([
                    'key' => $key,
                    'value' => $encryptedValue
                ]),
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
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
        } catch (\Exception $e) {
            return [
                'code' => 0,
                'success' => false,
                'error' => 'Encryption failed: ' . $e->getMessage(),
                'value' => ''
            ];
        }
    }
}

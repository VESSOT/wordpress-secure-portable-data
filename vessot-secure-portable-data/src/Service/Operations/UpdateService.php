<?php

declare(strict_types=1);

namespace Vessot\Service\Operations;

class UpdateService
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
        bool $isPartialUpdate = false,
        callable $encryptCallback = null
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

        // Value is already encrypted, no need for callback
        $requestData = ['key' => $key];

        if ($isPartialUpdate) {
            // Partial update with attributes
            $requestData['attributes'] = $value;
        } else {
            // Full value update
            $requestData['value'] = $value;
        }

        $response = wp_remote_request($this->apiUrl . '/update', [
            'method' => 'PUT',
            'body' => json_encode($requestData),
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
    }
}

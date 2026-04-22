<?php

namespace PinVandaag;

use PinVandaag\Exceptions\PinVandaagException;
use PinVandaag\Utils\Fallback;
use PinVandaag\Utils\Logger;

class Client
{
    private string $apiKey;
    private string $terminalId;
    private string $baseUrl;
    private Logger $logger;
    private string $backupUrl = "https://api-backup.pinvandaag.com";

    public function __construct(string $apiKey, string $terminalId, string $baseUrl = "https://rest-api.pinvandaag.com")
    {
        $this->apiKey = $apiKey;
        $this->terminalId = $terminalId;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->logger = new Logger();
    }

    public function setBaseUrl(string $url): void
    {
        $this->baseUrl = rtrim($url, '/');
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function setBackupUrl(string $url): void
    {
        $this->backupUrl = rtrim($url, '/');
    }

    public function getTerminalId(): string
    {
        return $this->terminalId;
    }

    public function request(string $endpoint, array $data = [], string $method = 'POST'): array
    {
        $url = $this->baseUrl . $endpoint;
        $method = strtoupper($method);

        if ($method === 'GET' && !empty($data)) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($data);
        }

        $this->logger->log("REQUEST", [
            'method' => $method,
            'url' => $url,
            'data' => $data,
        ]);

        $ch = curl_init($url);

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'X-API-KEY: ' . $this->apiKey,
                'Accept: application/json',
            ],
        ];

        if ($method === 'POST') {
            $payload = array_merge([
                'terminal_id' => $this->terminalId,
            ], $data);

            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = http_build_query($payload);
            $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/x-www-form-urlencoded';
            $options[CURLOPT_HTTPHEADER][] = 'User-Agent: PinVandaag-PHP-SDK/1.0.4';
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new PinVandaagException("Curl error: " . $error);
        }

        curl_close($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Normalize success responses (important for cancel)
        if ($httpCode === 204 || $httpCode === 200) {
            if (empty($response)) {
                return ['status' => 'success'];
            }
        }

        if ($httpCode === 204) {
            return ['status' => 204];
        }

        if ($httpCode >= 400) {
            $decoded = json_decode($response, true);

            if (is_array($decoded)) {
                return [
                    'status' => 'error',
                    'message' => $decoded['message'] ?? 'API error',
                    'data' => $decoded,
                ];
            }

            throw new PinVandaagException("HTTP error: $httpCode response: " . $response);
        }

        if ($response === '' || $response === null) {
            return ['status' => 'empty'];
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new PinVandaagException('Invalid JSON: ' . $response);
        }

        $this->logger->log("RESPONSE", $decoded);

        return $decoded;
    }

    public function requestWithFallback(string $endpoint, array $data = [], string $method = 'POST'): array
    {
        return Fallback::execute(
            fn() => $this->request($endpoint, $data, $method),
            function () use ($endpoint, $data, $method) {
                $this->logger->log("USING_BACKUP_API", $endpoint);

                $original = $this->baseUrl;
                $this->baseUrl = $this->backupUrl;

                try {
                    return $this->request($endpoint, $data, $method);
                } finally {
                    $this->baseUrl = $original;
                }
            },
            $this->logger
        );
    }

    public function success(string $message, array $data = []): array
    {
        return [
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ];
    }

    public function error(string $message, array $data = []): array
    {
        return [
            'status' => 'error',
            'message' => $message,
            'data' => $data,
        ];
    }
}
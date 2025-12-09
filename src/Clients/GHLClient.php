<?php
namespace Sync\Clients;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class GHLClient {
    private Client $http;

    public function __construct(string $baseUri, string $token) {
        $this->http = new Client([
            'base_uri' => rtrim($baseUri, '/'),
            'headers' => [
                'Authorization' => "Bearer $token",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'timeout' => 30
        ]);
    }

    public function post(string $path, array $json): array {
        try {
            $res = $this->http->post($path, ['json' => $json]);
            return json_decode($res->getBody()->getContents(), true) ?? [];
        } catch (GuzzleException $e) {
            throw new \RuntimeException("GHL POST failed: {$e->getMessage()}");
        }
    }

    public function put(string $path, array $json): array {
        try {
            $res = $this->http->put($path, ['json' => $json]);
            return json_decode($res->getBody()->getContents(), true) ?? [];
        } catch (GuzzleException $e) {
            throw new \RuntimeException("GHL PUT failed: {$e->getMessage()}");
        }
    }

    /**
     * Search contacts by custom field value.
     * Adjust payload shape if your GHL API version differs.
     */
    public function findByCustomField(string $locationId, string $key, string $value): ?array {
        $payload = [
            'locationId' => $locationId,
            'query' => [
                'customFields' => [[ 'key' => $key, 'value' => $value ]]
            ]
        ];
        $data = $this->post("/contacts/search", $payload);
        return $data['contacts'][0] ?? null;
    }

    public function createContact(string $locationId, array $payload): array {
        return $this->post("/contacts/", $payload + ['locationId' => $locationId]);
    }

    public function updateContact(string $contactId, string $locationId, array $payload): array {
        return $this->put("/contacts/$contactId", $payload + ['locationId' => $locationId]);
    }
}

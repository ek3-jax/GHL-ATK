<?php
namespace Sync\Clients;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class AutotaskClient {
    private Client $http;

    public function __construct(
        string $baseUri,
        string $username,
        string $secret,
        string $integrationCode
    ) {
        $this->http = new Client([
            'base_uri' => rtrim($baseUri, '/'),
            'headers' => [
                'UserName' => $username,
                'Secret' => $secret,
                'ApiIntegrationCode' => $integrationCode,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'timeout' => 30
        ]);
    }

    /** Generic GET */
    public function get(string $path, array $query = []): array {
        try {
            $res = $this->http->get($path, ['query' => $query]);
            return json_decode($res->getBody()->getContents(), true) ?? [];
        } catch (GuzzleException $e) {
            throw new \RuntimeException("Autotask GET failed: {$e->getMessage()}");
        }
    }

    /**
     * Query endpoint helper.
     * Autotask REST uses a JSON "search" param with filter structure.
     */
    public function query(string $entityPath, array $filter = [], int $page = 1): array {
        $search = ['filter' => $filter];
        return $this->get("$entityPath/query", [
            'search' => json_encode($search),
            'page' => $page
        ]);
    }

    /** Fetch all pages of an entity */
    public function fetchAll(string $entityPath, array $filter = []): array {
        $items = [];
        $page = 1;
        while (true) {
            $data = $this->query($entityPath, $filter, $page);
            $batch = $data['items'] ?? [];
            if (!$batch) break;
            $items = array_merge($items, $batch);
            $page++;
        }
        return $items;
    }

    public function getCompany(int $id): array {
        return $this->get("/V1.0/Companies/$id");
    }

    public function getContact(int $id): array {
        return $this->get("/V1.0/Contacts/$id");
    }

    public function listCompanies(): array {
        return $this->fetchAll("/V1.0/Companies");
    }

    public function listContactsForCompany(int $companyId): array {
        return $this->fetchAll("/V1.0/Contacts", [
            ['op' => 'eq', 'field' => 'companyID', 'value' => $companyId]
        ]);
    }

    /**
     * Query companies modified since datetime (ISO 8601 string).
     * Adjust field name if your Autotask tenant uses a different one.
     */
    public function listCompaniesModifiedSince(string $isoDatetime): array {
        return $this->fetchAll("/V1.0/Companies", [
            ['op' => 'gte', 'field' => 'lastModifiedDate', 'value' => $isoDatetime]
        ]);
    }

    public function listContactsModifiedSince(string $isoDatetime): array {
        return $this->fetchAll("/V1.0/Contacts", [
            ['op' => 'gte', 'field' => 'lastModifiedDate', 'value' => $isoDatetime]
        ]);
    }
}

<?php
namespace Sync\Services;

class Mapper {

    public function mapCompanyToGHL(array $co): array {
        $tags = ['AT:Company'];
        if (!($co['isActive'] ?? true)) $tags[] = 'AT:Inactive';

        return [
            'firstName' => $co['CompanyName'] ?? 'Unknown Company',
            'phone' => $co['Phone'] ?? null,
            'website' => $co['WebAddress'] ?? null,
            'address1' => $co['Address1'] ?? null,
            'city' => $co['City'] ?? null,
            'state' => $co['State'] ?? null,
            'postalCode' => $co['PostalCode'] ?? null,
            'tags' => $tags,
            'customFields' => [
                ['key' => 'autotask_company_id', 'value' => (string)$co['id']],
                ['key' => 'autotask_company_type', 'value' => (string)($co['CompanyType'] ?? '')],
                ['key' => 'autotask_company_status', 'value' => ($co['isActive'] ?? true) ? 'Active' : 'Inactive'],
            ]
        ];
    }

    public function mapContactToGHL(array $ct): array {
        $tags = ['AT:Contact'];
        if (!($ct['isActive'] ?? true)) $tags[] = 'AT:Inactive';
        if ($ct['isPrimaryContact'] ?? false) $tags[] = 'AT:Primary';

        return [
            'firstName' => $ct['firstName'] ?? '',
            'lastName'  => $ct['lastName'] ?? '',
            'email'     => $ct['emailAddress'] ?? null,
            'phone'     => $ct['phone'] ?? null,
            'tags'      => $tags,
            'customFields' => [
                ['key' => 'autotask_contact_id', 'value' => (string)$ct['id']],
                ['key' => 'autotask_company_id', 'value' => (string)($ct['companyID'] ?? '')],
                ['key' => 'autotask_is_primary', 'value' => ($ct['isPrimaryContact'] ?? false) ? 'true' : 'false'],
            ]
        ];
    }
}

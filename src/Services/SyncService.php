<?php
namespace Sync\Services;

use Sync\Clients\AutotaskClient;
use Sync\Clients\GHLClient;
use Sync\Utils\Logger;

class SyncService {

    public function __construct(
        private AutotaskClient $at,
        private GHLClient $ghl,
        private Mapper $mapper,
        private Logger $log,
        private string $locationId,
        private bool $dryRun = false
    ) {}

    public function upsertCompany(array $co): ?string {
        $atCompanyId = (string)$co['id'];
        $payload = $this->mapper->mapCompanyToGHL($co);

        $existing = $this->ghl->findByCustomField($this->locationId, 'autotask_company_id', $atCompanyId);
        if ($existing) {
            $this->log->info("Updating GHL company-contact", ['at_company_id'=>$atCompanyId, 'ghl_id'=>$existing['id']]);
            if (!$this->dryRun) {
                $this->ghl->updateContact($existing['id'], $this->locationId, $payload);
            }
            return $existing['id'];
        }

        $this->log->info("Creating GHL company-contact", ['at_company_id'=>$atCompanyId]);
        if ($this->dryRun) return null;

        $res = $this->ghl->createContact($this->locationId, $payload);
        return $res['contact']['id'] ?? null;
    }

    public function upsertContact(array $ct): ?string {
        $atContactId = (string)$ct['id'];
        $payload = $this->mapper->mapContactToGHL($ct);

        $existing = $this->ghl->findByCustomField($this->locationId, 'autotask_contact_id', $atContactId);
        if ($existing) {
            $this->log->info("Updating GHL contact", ['at_contact_id'=>$atContactId, 'ghl_id'=>$existing['id']]);
            if (!$this->dryRun) {
                $this->ghl->updateContact($existing['id'], $this->locationId, $payload);
            }
            return $existing['id'];
        }

        $this->log->info("Creating GHL contact", ['at_contact_id'=>$atContactId]);
        if ($this->dryRun) return null;

        $res = $this->ghl->createContact($this->locationId, $payload);
        return $res['contact']['id'] ?? null;
    }

    public function backfillAll(): void {
        $companies = $this->at->listCompanies();
        $this->log->info("Backfill companies count", ['count'=>count($companies)]);

        foreach ($companies as $co) {
            $this->upsertCompany($co);

            $contacts = $this->at->listContactsForCompany((int)$co['id']);
            foreach ($contacts as $ct) {
                $this->upsertContact($ct);
            }
        }
    }

    public function reconcileSince(string $isoDatetime): void {
        $companies = $this->at->listCompaniesModifiedSince($isoDatetime);
        $contacts  = $this->at->listContactsModifiedSince($isoDatetime);

        $this->log->info("Reconciling companies", ['count'=>count($companies), 'since'=>$isoDatetime]);
        foreach ($companies as $co) {
            $this->upsertCompany($co);
        }

        $this->log->info("Reconciling contacts", ['count'=>count($contacts), 'since'=>$isoDatetime]);
        foreach ($contacts as $ct) {
            $this->upsertContact($ct);
        }
    }
}

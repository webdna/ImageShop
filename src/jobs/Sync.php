<?php

namespace webdna\imageshop\jobs;

use Craft;
use craft\queue\BaseJob;
use craft\helpers\Json;
use webdna\imageshop\ImageShop;

/**
 * Syncs the details of documnets from the recently updated cache to all the
 * content rows in the db that contain imageshop assets
 */
class Sync extends BaseJob
{
    public string $rowId = '';
    public string $rowUid = '';
    public string $documentIds = '';
    public string $fields = '';
    public int $index = 0;
    public int $count = 0;

    function execute($queue): void
    {
        ImageShop::getInstance()->service->updateContentRow([
            'rowId' => $this->rowId,
            'rowUid' => $this->rowUid,
            'documentIds' => Json::decode($this->documentIds),
            'fields' => Json::decode($this->fields),
        ]);
    }

    protected function defaultDescription(): ?string
    {
        return "Re-syncing imageshop data {$this->index} of {$this->count}";
    }
}

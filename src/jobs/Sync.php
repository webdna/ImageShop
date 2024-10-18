<?php

namespace webdna\imageshop\jobs;

use Craft;
use craft\queue\BaseJob;
use webdna\imageshop\ImageShop;

/**
 * Sync queue job
 */
class Sync extends BaseJob
{
    public $id;
    public $index;
    public $total;

    function execute($queue): void
    {
        ImageShop::getInstance()->service->updateDocumentById($this->id);
    }

    protected function defaultDescription(): ?string
    {
        return "Re-syncing data for imageshop document {$this->id}: {$this->index} of {$this->count}";
    }
}

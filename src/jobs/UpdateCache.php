<?php

namespace webdna\imageshop\jobs;

use Craft;
use craft\queue\BaseJob;
use webdna\imageshop\ImageShop;

/**
 * Gets all the data of documents that have chagned since the last time the scan 
 * was run, and saves them in the db in a dump.
 */
class UpdateCache extends BaseJob
{
    function execute($queue): void
    {
        ImageShop::getInstance()->service->updateRecentlyUpdatedCache();
    }

    protected function defaultDescription(): ?string
    {
        return "Getting recently changed ImageShop Dam documents";
    }
}

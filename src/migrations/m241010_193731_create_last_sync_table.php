<?php

namespace webdna\imageshop\migrations;

use Craft;
use craft\db\Migration;

/**
 * m241010_193731_create_last_sync_table migration.
 */
class m241010_193731_create_last_sync_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->createTable('{{%imageshop-dam_sync}}', [
            'lastUpdated' => $this->dateTime(),
            'documentCache' => $this->longText()
        ]);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropTableIfExists('{{%imageshop-dam_sync}}');
        
        return false;
    }
}

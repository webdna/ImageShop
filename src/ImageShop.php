<?php
/**
 * ImageShop plugin for Craft CMS 4.x
 *
 * ImageShop Integration for CraftCMS
 *
 * @link      https://webdna.co.uk
 * @copyright Copyright (c) 2022 WebDNA
 */

namespace webdna\imageshop;

use webdna\imageshop\services\ImageShop as Service;
use webdna\imageshop\models\Settings;
use webdna\imageshop\fields\ImageShopField;

use Craft;
use craft\base\Plugin;
use craft\base\Model;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\services\Fields;
use craft\events\RegisterComponentTypesEvent;

use yii\base\Event;

/**
 * Class ImageShop
 *
 * @author    WebDNA
 * @package   ImageShop
 * @since     2.0.0
 *
 * @property  ImageShopServiceService $imageShopService
 */
class ImageShop extends Plugin
{
    // Static Properties
    // =========================================================================

    public static ImageShop $plugin;

    // Public Properties
    // =========================================================================

    public string $schemaVersion = '2.0.0';

    public bool $hasCpSettings = true;

    public bool $hasCpSection = false;

    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();
        self::$plugin = $this;
            
        $this->setComponents([
            'service' => Service::class,
        ]);

        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = ImageShopField::class;
            }
        );

        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                }
            }
        );

        Craft::info(
            Craft::t(
                'imageshop-dam',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'imageshop-dam/settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }
}

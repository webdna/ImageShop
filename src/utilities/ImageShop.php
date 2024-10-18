<?php

namespace webdna\imageshop\utilities;

use Craft;
use craft\base\Utility;

/**
 * Sync utility
 */
class ImageShop extends Utility
{
    public static function displayName(): string
    {
        return Craft::t('imageshop-dam', 'ImageShop');
    }

    static function id(): string
    {
        return 'imageshop-dam';
    }

    public static function iconPath(): ?string
    {
        return null;
    }

    static function contentHtml(): string
    {
        // todo: replace with custom content HTML
        $view = Craft::$app->getView();
        return $view->renderTemplate('imageshop-dam/_components/utilities/index.twig');
    }
}



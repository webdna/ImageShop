<?php

namespace webdna\imageshop\utilities;

use Craft;
use craft\base\Utility;
use webdna\imageshop\ImageShop as Plugin;

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
        $view = Craft::$app->getView();
        $documentCacheExists = !empty(Plugin::getInstance()->service->getDocumentCache());
        return $view->renderTemplate(
            'imageshop-dam/_components/utilities/index.twig',
            [
                'canRunDbUpdate' => $documentCacheExists
            ]
        );
    }
}



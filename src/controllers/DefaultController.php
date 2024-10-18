<?php

namespace webdna\imageshop\controllers;

use craft\web\Controller;
use webdna\imageshop\ImageShop;
use yii\web\Response;

use craft;


class DefaultController extends Controller
{
    public function actionCreateSyncJobs(): ?Response
    {
        // lets get all the imageshop fields then we can get each json from the content table
        // then for each item in the content table we can get the doc Id
        // cross ref the table of ids against changed ids and the last ran value in the db
        // CREATE A DB TABLE THAT CONTAINS THE LAST DATE OF SYNC RUN
        $recentlyUpdatedIds = ImageShop::getInstance()->service->getRecentlyUpdated();
        $imageShopDbRows = ImageShop::getInstance()->service->getAllImageShopImages();
        $documentIds = ImageShop::getInstance()->service->getDocumentIdsFromImages($imageShopDbRows);

        // do the upgrade
        $update = ImageShop::getInstance()->service->updateImages($imageShopDbRows, $recentlyUpdatedIds);
        // $getRecentlyUpdatedAssets = ImageShop::getInstance()->service->getRecentlyUpdatedAssets(recentlyUpdatedIds);
        // $totalIds = count($recentlyUpdatedIds);
        // foreach ($recentlyUpdatedIds as $key => $value) {
        //     ImageShop::getInstance()->service->createSyncJob($value, $key + 1, $totalIds);
        // }
        return $this->redirectToPostedUrl();
    }
}
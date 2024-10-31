<?php

namespace webdna\imageshop\controllers;

use craft\web\Controller;
use webdna\imageshop\jobs\UpdateCache;
use yii\web\Response;

use craft;
use webdna\imageshop\ImageShop;

class DefaultController extends Controller
{
    public function actionGetChangedDocuments(): ?Response
    {
        Craft::$app->getQueue()->ttr(3600)->push(new UpdateCache());

        return $this->redirectToPostedUrl();
    }
    
    public function actionCreateSyncJobs(): ?Response
    {
        ImageShop::getInstance()->service->updateImages();
        
        return $this->redirectToPostedUrl();
    }
}
<?php
/**
 * ImageShop plugin for Craft CMS 3.x
 *
 * ImageShop Integration for CraftCMS
 *
 * @link      https://webdna.co.uk
 * @copyright Copyright (c) 2022 WebDNA
 */

namespace webdna\imageshop\services;

use webdna\imageshop\ImageShop as Plugin;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\App;
use craft\helpers\Db;
use craft\helpers\Json;
use DateTime;
use GuzzleHttp\Client;
use webdna\imageshop\fields\ImageShopField;
use webdna\imageshop\jobs\Sync;

/**
 * @author    WebDNA
 * @package   ImageShop
 * @since     2.0.0
 */
class ImageShop extends Component
{
    // Public Methods
    // =========================================================================

    public function getTemporaryToken(): mixed
    {
        $settings = Plugin::$plugin->getSettings();
            
        // If no token is sent or set in settings
        if (empty($settings->token) || empty($settings->key)) {
            return null;
        }
        return $this->_request('GET','/Login/GetTemporaryToken',[
            'query' => [
                'privateKey' => App::parseEnv($settings->key)
            ]
        ]);
    }

    public function getDocumentById(int $documentId, string $language): ?array
    {
        if (!$documentId || !$language) {
            return null;
        }
        
        $response = $this->_request('GET','/Document/GetDocumentById',[
            'query' => [
                'DocumentID' => $documentId,
                'language' => $language
            ]
        ]);

        if (!Json::isJsonObject($response)) {
            return null;
        }

        return Json::decode($response);
    }

    public function getImageShopFields(): array
    {
        $imageShopFields = Craft::$app->getFields()->getFieldsByType(ImageShopField::class);
        $fields = [];
        foreach ($imageShopFields as $field) {
            $columnName = '';
            if ($field->columnPrefix) {
                $columnName .= $field->columnPrefix . '_';
            }
            $columnName .= 'field_' . $field->handle;
            if ($field->columnSuffix) {
                $columnName .= '_' . $field->columnSuffix;
            }
            $fields[] = $columnName;
        }

        return $fields;
    }

    public function getAllImageShopImages(): array
    {
        $fields = $this->getImageShopFields();

        $imagesQuery = (new Query())
            ->select('*')
            ->from(Table::CONTENT);

            
            foreach ($fields as $field) {
                $imagesQuery->andWhere(['not', [$field => null]]);
            }
        // would be better to do this with something like JSON_CONTAINS but 
        // can't be certain about db driver or version on system.


        $images = [];
        foreach ($imagesQuery->all() as $value) {
            $image = [
                'rowId' => $value['id'],
                'rowUid' => $value['uid'],
                'documentIds' => [],
                'fields' => []
            ];
            foreach ($fields as $field) {
                if (array_key_exists($field,$value) && Json::isJsonObject($value[$field])) {
                    $fieldValue = Json::decode($value[$field]);
                    // deal with pre-allow multiple update
                    if (!is_array($fieldValue)) {
                       $fieldValue = [$fieldValue];
                    }
                    // Craft::dd($fieldValue);
                    foreach ($fieldValue as $value) {
                        $imageData = Json::decode($value);
                        $image['documentIds'][] = $imageData['documentId'];
                        $image['fields'][$field][($imageData['documentId'])] = $imageData;
                    }
                }
            }
            $images[] = $image;
        }
        return $images;

    }

    public function getNewImageData($dbRows, $recentlyUpdatedIds): bool
    {
        $settings = Plugin::$plugin->getSettings();
        $documentCache = [];
        $documentIds = $this->getDocumentIdsFromImages($dbRows);
        $forUpdate = array_intersect($documentIds, $recentlyUpdatedIds);

        foreach ($forUpdate as $documentId) {
            $documentCache[$documentId] = $this->getDocumentById($documentId,$settings->language);
        }

        $this->_setDocumentCache($documentCache);
      
        return true;
    }

    public function getDocumentIdsFromImages(array $images): array
    {
        return array_unique(array_merge(...array_column($images,'documentIds')));
    }

    
    public function getRecentlyUpdated(): array
    {
        $lastUpdate = $this->_getDateLastUpdated();
        $response = $this->_request('GET','/Document/GetAllDocumentIdsChangedAfter',[
            'query' => [
                'changed' => $lastUpdate
                ]
            ]);
        $ids = Json::decodeIfJson($response);
        
        return $ids;
    }
    
    public function createSyncJob(int $id, int $index, int $total): void
    {
        Craft::$app->getQueue()->ttr(3600)->push(new Sync([
            'id' => $id,
            'index' => $index,
            'total' => $total
        ]));
    }
        
    public function _setDocumentCache($documentCache): bool
    {
        $lastUpdate = Db::prepareDateForDb(date('m/d/Y h:i:s a', time()));
        Craft::$app->getDb()
            ->createCommand()
            ->update('{{%imageshop-dam_sync}}', [
                'lastUpdated' => $lastUpdate,
                'documentCache' => Json::encode($documentCache)
            ])
            ->execute();

        return true;
    }

    private function _getDateLastUpdated(): string
    {
        $query = (new Query())
            ->select('lastUpdated')
            ->from('{{%imageshop-dam_sync}}')
            ->one();

        return $query['lastUpdated'];
    }

    private function _request(string $method='GET', string $action='', array $params=[]): mixed
    {
        $settings = Plugin::$plugin->getSettings();
        // If no token is sent or set in settings
        if (empty($settings->token) || empty($settings->key)) {
            return null;
        }

        $client = new Client([
            'base_uri' => 'https://api.imageshop.no',
            'headers' => [
                'Token' => App::parseEnv($settings->token),
                'Accept' => 'application/json',
                'Content-Type' => 'application/xml'
            ]
        ]);
        
        $response = $client->request($method,$action,$params);
        
        if ($response->getStatusCode() == '200' && $response->hasHeader('Content-Length')) {
            return $response->getBody()->getContents();
        }

        return null;
    }
}

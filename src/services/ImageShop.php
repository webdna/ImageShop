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

use webdna\imageshop\fields\ImageShopField;
use webdna\imageshop\ImageShop as Plugin;
use webdna\imageshop\jobs\Sync;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\App;
use craft\helpers\Db;
use craft\helpers\Json;

use GuzzleHttp\Client;

/**
 * @author    WebDNA
 * @package   ImageShop
 * @since     2.0.0
 */
class ImageShop extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * Get a temporary access token
     * 
     * @return mixed
     **/
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
    /**
     * Gets a document from the imageshop API
     *
     *
     * @param int $documentId Document Id
     * @param string $language Requested language
     * @return ?array document data
     **/
    public function getDocumentById(int $documentId, string $language): ?array
    {
        if (!$documentId) {
            return null;
        }

        $language = $this->sanitizeLanguage($language);
        
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

    /**
     * gets all the imageshop field column names for the content table
     *
     * @return array an array of column names
     **/
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

    /**
     * Get all rows from the content table that contain an imageshop field value
     * and format them into format with keys: 'rowId','rowUid','documentIds','fields' (contains all document data)
     *
     * @return array 
     **/
    public function getAllImageShopContentRows(): array
    {
        $fields = $this->getImageShopFields();

        $rowsQuery = (new Query())
            ->select('*')
            ->from(Table::CONTENT);

            
        foreach ($fields as $field) {
            $rowsQuery->andWhere(['not', [$field => null]]);
        }
        // would be better to do this with something like JSON_CONTAINS but 
        // can't be certain about db driver or version on system.


        $rows = [];
        foreach ($rowsQuery->all() as $value) {
            $row = [
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
                    foreach ($fieldValue as $v) {
                        $imageData = is_array($v) ? $v : Json::decodeIfJson($v);
                        $row['documentIds'][] = $imageData['documentId'];
                        $row['fields'][$field][($imageData['documentId'])] = $imageData;
                    }
                }
            }
            $rows[] = $row;
        }
        return $rows;

    }

    /**
     * Updates the content of an imageshop field with data from the
     * recently updated cache
     * 
     * @param array $config Details of the row to be updated, must contain 'rowId','rowUid' and 'documentId'
     **/
    public function updateContentRow(array $config): void
    {
        $fieldColumnNames = $this->getImageShopFields();
        $updatedDocuments = $this->getDocumentCache();
        
        $rowsQuery = (new Query())
            ->select($fieldColumnNames)
            ->from(Table::CONTENT)
            ->where([
                'id' => $config['rowId'],
                'uid' => $config['rowUid']
            ])
            ->one();

        $newData = [];
        foreach ($fieldColumnNames as $columnName) {
            $oldData = Json::decodeIfJson($rowsQuery[$columnName]);
            $newData[$columnName] = [];
            foreach ($oldData as $documentJson) {
                $document = Json::decodeIfJson($documentJson);
                if (array_key_exists($document['documentId'], $updatedDocuments)) {
                    $newData[$columnName][] = $this->mapDocumentFields($document,$updatedDocuments[$document['documentId']]); 
                } else {
                    $newData[$columnName][] = $document;
                }
            }
            $newData[$columnName] = Json::encode($newData[$columnName]);
        }

        Craft::$app->getDb()
            ->createCommand()
            ->update(
                Table::CONTENT, 
                $newData, 
                [
                    'id' => $config['rowId'],
                    'uid' => $config['rowUid']
                ]
            )
            ->execute();
        
        return;
    }

    /**
     * Maps API data for sync to the stored field values, currently just alt-text
     *
     * @param array $dataFromPicker Data model that comes from the imageshop image picker pop up
     * @param array $dataFromApi Data model that comes from API during sync
     * @return array $mapped The updated data in the form of the picker data
     **/
    public function mapDocumentFields(array $dataFromPicker, array $dataFromApi): array
    {
        $settings = Plugin::getInstance()->getSettings();
        $language = $settings->language;
        // currently just alttext update
        $mapped = $dataFromPicker;
        
        if (array_key_exists($language,$mapped['text'])) {
            $mapped['text'][$language]['altText'] = $dataFromApi['altText'];
        }
        
        return $mapped;
    }

    /**
     * Updates the recently updated dump in the db
     *
     * @return void
     **/
    public function updateRecentlyUpdatedCache(): void
    {
        $recentlyUpdatedIds = $this->_getRecentlyUpdated();
        $imageShopDbRows = $this->getAllImageShopContentRows();
        $this->_getNewImageData($imageShopDbRows, $recentlyUpdatedIds);
    }


    /**
     * Creates the recently updated document cache using the getDocumentById API call
     * 
     * @param array $dbRows data from content table row in the format 'rowId','rowUid','documentIds','fields' (contains all document data)
     * @param array $recentlyUpdatedIds Document Ids from the recently updated api call
     **/
    private function _getNewImageData(array $dbRows, $recentlyUpdatedIds): void
    {
        $settings = Plugin::$plugin->getSettings();
        $documentCache = [];
        $documentIds = $this->_getDocumentIdsFromImages($dbRows);
        $forUpdate = array_intersect($documentIds, $recentlyUpdatedIds);

        if (count($forUpdate) === 0) {
            return;
        }

        foreach ($forUpdate as $documentId) {
            $documentCache[$documentId] = $this->getDocumentById($documentId,$settings->language);
        }


        $this->_setDocumentCache($documentCache);
      
        return;
    }

    /**
     * Takes the formatted content table rows and returns all the documentIds in the whole site.
     *
     * @param array $images Content rows
     * @return array Just the DocumentIds
     **/
    private function _getDocumentIdsFromImages(array $images): array
    {
        return array_unique(array_merge(...array_column($images,'documentIds')));
    }

    /**
     * gets the recently updated documents from the imageshop API using the last time the 
     * update was run as the date.
     *     *
     * @return array Array of DocumentIds
     * @throws conditon
     **/
    private function _getRecentlyUpdated(): array
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

    /**
     * Creates the queue jobs to update all the relevent content rows in the db with the latest imageshop image data
     **/
    public function updateImages(): void
    {
        // check if there is anything to do
        $documentCache = $this->getDocumentCache();

        if (count($documentCache) == 0) {
            return;
        }

        $contentRows = $this->getAllImageShopContentRows();
        $index = 0;
        $total = count($contentRows);

        foreach ($contentRows as $row) {
            Craft::$app->getQueue()->ttr(3600)->push(new Sync([
                'rowId' => $row['rowId'],
                'rowUid' => $row['rowUid'],
                'documentIds' => Json::encode($row['documentIds']),
                'fields' => Json::encode($row['fields']),
                'index' => $index++,
                'count' => $total
            ]));
        }
        return;
    }

    /**
     * sometimes the language code doesn't mnatch with the API, this tries to match the relevent one, or any.
     *
     * @param string $lang Language
     * @return string Sanitized language
     **/
    public function sanitizeLanguage(string $lang = null): ?string
    {
        $settings = Plugin::getInstance()->getSettings();
        if (!$lang) {
            $settings = Plugin::$plugin->getSettings();
            switch ($settings->language) {
                case 'nb-NO':
                    $lang = 'no';
                    break;
                    
                case 'en-US':
                    $lang = 'en';
                    break;
                    
                default:
                    $lang = 'no';
                    break;
            }
        } else {
            if (!in_array($lang, ["no", "en", "sv"])) {
                $lang = "no";
            }
        }
        
        return $lang;
    }

    /**
     * Gets the recently updated document cache from the db
     *
     * @return array|string The document cache
     **/
    public function getDocumentCache(): array|string
    {
        $query = (new Query())
            ->select('documentCache')
            ->from('{{%imageshop-dam_sync}}')
            ->one();

        return Json::decodeIfJson($query['documentCache']);
    }
        
    /**
     * writes the new document cache to the db
     *
     * @param array $documentCache The new document data
     * @return bool
     **/
    private function _setDocumentCache(array $documentCache): bool
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

    /**
     * Gets the last time the update was ran
     *
     * @return string The lastest date updated
     **/
    private function _getDateLastUpdated(): string
    {
        $query = (new Query())
            ->select('lastUpdated')
            ->from('{{%imageshop-dam_sync}}')
            ->one();

        return $query['lastUpdated'];
    }

    /**
     * base api call helper
     *
     * @param string $method default GET
     * @param string $action The target endpoint
     * @param string $params Params to be included in the call
     * @return mixed
     **/
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

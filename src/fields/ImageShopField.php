<?php
/**
 * ImageShop plugin for Craft CMS 3.x
 *
 * ImageShop Integration for CraftCMS
 *
 * @link      https://webdna.co.uk
 * @copyright Copyright (c) 2022 WebDNA
 */

namespace webdna\imageshop\fields;

use webdna\imageshop\ImageShop;
use webdna\imageshop\models\ImageShop as Model;
use webdna\imageshop\assetbundles\imageshop\ImageShopAsset;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\helpers\Db;
use yii\db\Schema;
use craft\helpers\Json;

/**
 * @author    WebDNA
 * @package   ImageShop
 * @since     2.0.0
 */
class ImageShopField extends Field
{
    // Public Properties
    // =========================================================================

    public bool $showSizeDialogue = false;
    
    public bool $showCropDialogue = false;
    
    public string $sizes = 'Normal;1920x0';
    

    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('imageshop-dam', 'ImageShop DAM');
    }

    // Public Methods
    // =========================================================================


    /**
     * @inheritdoc
     */
    public function getContentColumnType(): array|string
    {
        return Schema::TYPE_TEXT;
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue($value, ElementInterface $element = null): Model
    {
        if ($value instanceof Model) {
            return $value;
        }
        
        return new Model($value);
    }

    /**
     * @inheritdoc
     */
    public function serializeValue($value, ElementInterface $element = null): mixed
    {
        return parent::serializeValue($value, $element);
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        // Render the settings template
        return Craft::$app->getView()->renderTemplate(
            'imageshop-dam/_components/fields/settings',
            [
                'field' => $this,
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        $settings = ImageShop::$plugin->getSettings();
        $token = ImageShop::$plugin->service->getTemporaryToken();
        $token = $token->GetTemporaryTokenResponse->GetTemporaryTokenResult;
        
        $query = http_build_query([
            "IMAGESHOPTOKEN" => (string) $token,
            "SHOWSIZEDIALOGUE" => $this->showSizeDialogue ? 'true' : 'false',
            "SHOWCROPDIALOGUE" => $this->showCropDialogue ? 'true' : 'false',
            "IMAGESHOPSIZES" => $this->sizes,
            "FORMAT" => "json",
            "SETDOMAIN" => "false",
            "CULTURE" => $settings->language,
        ]);
        
        $url = sprintf("%s?%s", "https://client.imageshop.no/insertimage2.aspx", trim($query, "&"));
        
        // Register our asset bundle
        Craft::$app->getView()->registerAssetBundle(ImageShopAsset::class);

        // Get our id and namespace
        $id = Craft::$app->getView()->formatInputId($this->handle);
        $namespacedId = Craft::$app->getView()->namespaceInputId($id);

        // Variables to pass down to our field JavaScript to let it namespace properly
        $jsonVars = [
            'id' => $id,
            'name' => $this->handle,
            'namespace' => $namespacedId,
            'prefix' => Craft::$app->getView()->namespaceInputId(''),
            'url' => $url,
            ];
        $jsonVars = Json::encode($jsonVars);
        Craft::$app->getView()->registerJs("new Craft.ImageShopDAMField(" . $jsonVars . ");");

        // Render the input template
        return Craft::$app->getView()->renderTemplate(
            'imageshop-dam/_components/fields/input',
            [
                'name' => $this->handle,
                'value' => $value,
                'field' => $this,
                'id' => $id,
                'namespace' => $namespacedId,
            ]
        );
    }

}

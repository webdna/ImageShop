<?php
/**
 * ImageShop plugin for Craft CMS 3.x
 *
 * ImageShop Integration for CraftCMS
 *
 * @link      https://webdna.co.uk
 * @copyright Copyright (c) 2022 WebDNA
 */

namespace webdna\imageshop\models;

use webdna\imageshop\ImageShop;

use Craft;
use craft\base\Model;

/**
 * @author    WebDNA
 * @package   ImageShop
 * @since     2.0.0
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    public string $token = '';
    
    public string $key = '';
    
    public string $language = 'no';
    

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['token', 'key', 'language'], 'string'],
            [['token', 'key', 'language'], 'required'],
        ];
    }
}

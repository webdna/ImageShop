<?php

namespace webdna\imageshop\gql\types;

use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class ImageShopType
{
    static public function getName(): string
    {
        return 'ImageShop_Image';
    }

    static public function getType(): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::class)) {
            return $type;
        }

        return GqlEntityRegistry::createEntity(self::class, new ObjectType([
            'name'   => static::getName(),
            'fields' => self::class . '::getFieldDefinitions',
            'description' => 'The interface implemented by all ImageShop types.',
        ]));
    }

    public static function getFieldDefinitions(): array
    {
        return [
            'url' => [
                'name' => 'url',
                'type' => Type::string(),
                'description' => 'The image URL.',
            ],
            'credits' => [
                'name' => 'credits',
                'type' => Type::string(),
                'description' => 'The credits for the image.',
            ],
            'description' => [
                'name' => 'description',
                'type' => Type::string(),
                'description' => 'The description of the image.',
            ],
        ];
    }
}


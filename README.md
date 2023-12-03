# Official Imageshop plugin for Craft CMS

This official plugin integrates [Imageshop Digital Asset Management system](https://www.imageshop.org) with Craft CMS by exposing
their image selector as a popup that saves the selected image data in a field so the selection
can be used in twig templates.



 ![Screenshot](./screenshot.png)


# Installation

To install the plugin, follow these instructions.

- Open your terminal and go to your Craft project:

````
cd /path/to/project
````

- Then tell Composer to load the plugin:

```
composer require webdna/imageshop-dam
```

- In the Control Panel, go to Settings → Plugins and click the “Install” button for 'Imageshop'.

OR do it via the command line

```
php craft plugin/install imageshop-dam
```

- On the settings page, fill out the token and private key field to start using the plugin.

- You will now have access to the "Imageshop" in the Field type dropdown on the field creation page.



## Templating:


### Plain and simple

```twig
<img src="{{ entry.imageshopField.url }}" alt="{{ entry.imageshopField.filename }}">
```

### Using Imager

## Single size

```twig
{% set image = craft.imager.transformImage(entry.imageshopField.url, { width: 400 }) %}
<img src="{{ image.url }}">
```


## Multiple sizes
```twig
{% set transforms = craft.imager.transformImage(
    entry.imageshopField.url,
    [
        { width: 200 },
        { width: 800 },
        { width: 1200 },
        { width: 1920 }
    ]
    ) %}

{% for image in transforms %}
    <img src="{{ image.url }}" width="{{ image.width }}" style="width: auto;margin: 20px;">
{% endfor %}
```


## Responsive images with srcset

```twig
{% set transformedImages = craft.imager.transformImage(image,[
        { width: 1920, jpegQuality: 90, webpQuality: 90 },
        { width: 1200, jpegQuality: 75, webpQuality: 75 },
        { width: 800, jpegQuality: 75, webpQuality: 75 },
        { width: 400, jpegQuality: 65, webpQuality: 65 },
    ]) %}

<img srcset="{{ craft.imager.srcset(transformedImages) }}">
```




### Available attributes

```imageshopField``` is the name of the field in these examples.

 ```twig
Code:           {{ entry.imageshopField.code }}
Image:          {{ entry.imageshopField.image }}
Tags:           {{ entry.imageshopField.tags("no") | join(", ") }}
Title:          {{ entry.imageshopField.title }}
Rights:         {{ entry.imageshopField.rights }}
Description:    {{ entry.imageshopField.description }}
Credit:         {{ entry.imageshopField.credits }}
DocumentId:     {{ entry.imageshopField.documentId }}
Raw:            {{ entry.imageshopField.json | json_encode(constant("JSON_PRETTY_PRINT")) }}
```



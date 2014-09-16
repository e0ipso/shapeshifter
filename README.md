[![Build Status](https://travis-ci.org/mateu-aguilo-bosch/shapeshifter.svg?branch=7-x-1.x)](https://travis-ci.org/mateu-aguilo-bosch/shapeshifter) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mateu-aguilo-bosch/shapeshifter/badges/quality-score.png?b=7.x-1.x)](https://scrutinizer-ci.com/g/mateu-aguilo-bosch/shapeshifter/?branch=7.x-1.x)

# Shapeshifter

![Shapeshifter](http://www.animaatjes.nl/plaatjes/m/my_little_pony/animaatjes-my_little_pony-83953.png)

This module aims to solve a generic problem when working with data
transformations, _how do I generate a certain data structure from my entity_.
Imagine that you want a JSON output like the following for a given article:

```js
{
  "root": {
    "title": "Built by robots.",
    "description": "...",
    "images": [
      {
        "original": "https://example.org/image1.jpeg",
        "thumbnail": "https://example.org/styles/thumbnail/image1.jpeg",
        "square": "https://example.org/styles/square/image1.jpeg"
      },
      {
        "original": "https://example.org/image2.jpeg",
        "thumbnail": "https://example.org/styles/thumbnail/image2.jpeg",
        "square": "https://example.org/styles/square/image2.jpeg"
      }
    ],
    "basic_info": {
      "nid": 334,
      "vid": 559
    }
  }
}
```

## Setting up your mapper.

In order to do so you need to create a custom module that will contain a *mapper
plugin*. You can use the example module as an starting point. In there you will
need to create a class that implements the `getMappingsInfo()` method (again,
just copy and paste). That method needs to return an array that will map the
array structure to the actual information in the article node. That looks like:

```php
  /**
   * Implements \ShapeshifterMapperBase::getMappingInfo().
   */
  public static function getMappingsInfo() {
    return array(
      'root::title' => array(
        'property' => 'title',
      ),
      'root::description' => array(
        'property' => 'field_body',
        'sub_property' => 'value',
      ),
      'root::images' => array(
        'property' => 'field_image',
        'process_callbacks' => array(
          'static::getImageDerivatives'
        ),
      ),
      'root::basic_info::nid' => array(
        'wrapper_method' => 'getIdentifier',
        'wrapper_method_on_entity' => TRUE,
      ),
      'root::basic_info::vid' => array(
        'property' => 'vid',
      ),
    );
  }

  /**
   * Dummy function to show how to process fields. Returns image variants.
   *
   * @param array $file_array
   *   The image file.
   *
   * @return array
   *   The output for this.
   */
  public static function getImageDerivatives(array $file_array) {
    $uri = $file_array['uri'];
    $return = array(
      'original' => file_create_url($uri),
    );

    $image_styles = array(
      'thumbnail',
      'square',
    );

    foreach ($image_styles as $image_style) {
      $return[$image_style] = image_style_url($image_style, $uri);
    }
    return $return;
  }
```

And that's it. Your mapper is in place!

## Calling your mapper.

You will only need to get the correct mapper using the correct plugin name,
select the entity ID, and then call `map()`.

Following our example, if we wanted to display the array info in a page:

```php
/**
 * Page callback for /mapper/{nid}.
 */
function my_module_page_callback($nid) {
  $mapper = shapeshifter_get_plugin_handler('plugin_name', 'mapper');
  $mapper->setEntityId($nid);

  return '<pre>' . print_r($mapper->map(), TRUE) . '</pre>';
}
```

## Encoding your output.

The output formatters are also plugins, that means that you can declare your own
output formatters to encode the structured array that comes from the mapper (or
any array really). This module comes with two formats by default that can be
explored as an example to create your custom formatters: JSON and XML.

To encode an array just do:

```php
/**
 * Page callback for /mapper/{nid}.
 */
function my_module_page_callback($nid) {
  $mapper = shapeshifter_get_plugin_handler('plugin_name', 'mapper');
  $mapper->setEntityId($nid);

  // The array that we want to output.
  $data = $mapper->map();

  // Create the formatter change 'json' to 'xml' or to your formatter plugin
  // name to use different output formats.
  $formatter = shapeshifter_get_plugin_handler('json', 'formatter');

  return '<pre>' . $formatter->format($data) . '</pre>';
}
```

### Preparing your output.

Sometimes you'll need small modifications to the output array before outputting
it. For instance HAL+JSON will add a '_links' element with some metadata, or you
want to reorder the elements under special circumstances. To do so you just need
to implement the `prepare()` method in your custom formatter plugin.

```php
  ...
  /**
   * Overwrites \ShapeshifterFormatterBase::prepare().
   */
  public function prepare(array $data) {
    return array(
      '_data' => $data,
      '_links' => $this->generateLinks($data),
    );
  }
  ...
```

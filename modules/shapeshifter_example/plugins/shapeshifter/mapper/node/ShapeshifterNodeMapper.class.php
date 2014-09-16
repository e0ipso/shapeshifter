<?php

/**
 * @file
 * Contains \ShapeshifterNodeMapper
 */

class ShapeshifterNodeMapper extends \ShapeshifterMapperBase {

  /**
   * Implements \ShapeshifterMapperBase::getMappingInfo().
   */
  public static function getMappingsInfo() {
    return array(
      'root' . static::PATH_SEPARATOR . 'title' => array(
        'property' => 'title',
      ),
      'root' . static::PATH_SEPARATOR . 'label' => array(
        'wrapper_method' => 'label',
        'wrapper_method_on_entity' => TRUE,
      ),
      'root' . static::PATH_SEPARATOR . 'basic' . static::PATH_SEPARATOR . 'id' => array(
        'wrapper_method' => 'getIdentifier',
        'wrapper_method_on_entity' => TRUE,
      ),
      'root' . static::PATH_SEPARATOR . 'basic' . static::PATH_SEPARATOR . 'nid' => array(
        'property' => 'nid',
        'process_callbacks' => array(
          'static::processNid',
        ),
      ),
    );
  }

  /**
   * Dummy function to show how to process fields.
   *
   * @param int $nid
   *   The node ID.
   *
   * @return string
   *   The nid processed.
   */
  public static function processNid($nid) {
    return t('Node ID: @nid', array(
      '@nid' => $nid,
    ));
  }

  /**
   * Dummy function to show how to process fields.
   *
   * @param array $file_array
   *   The image file.
   *
   * @return array
   *   The output for this.
   */
  public static function getImageDerivatives($file_array) {
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

}

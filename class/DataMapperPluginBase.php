<?php

/**
 * @file
 * Contains \DataMapperPluginBase.
 */

class DataMapperPluginBase {

  /**
   * @var array
   *
   * The plugin definition array.
   */
  protected $plugin;

  /**
   * Class constructor.
   *
   * @param array $plugin
   *   The plugin definition array.
   */
  public function __construct(array $plugin) {
    $this->plugin = $plugin;
  }

  /**
   * Gets the plugin definition array for the selected property or all.
   *
   * @param string $key
   *   The plugin property to get. NULL to get all properties.
   *
   * @return array
   *   The plugin definition for the selected property or all properties.
   *
   * @throws \DataMapperException
   */
  public function getPuginInfo($key = NULL) {
    if (empty($key)) {
      return $this->plugin;
    }
    if (isset($this->plugin[$key])) {
      return $this->plugin[$key];
    }
    // If we could not find the property throw an exception.
    throw new \DataMapperException(format_string('The selected plugin property "@property" does not exist.', array(
      '@property' => $key,
    )));
  }

}
